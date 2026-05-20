<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Core\Configure;
use Cake\Database\Driver\Mysql;
use Cake\Datasource\FactoryLocator;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorInterface;
use RuntimeException;
use Throwable;

class PaymentRefundService
{
    private object $paymentsTable;
    private object $refundsTable;
    private object $bookingsTable;
    private StripeRefundGatewayInterface $gateway;
    private PaymentAdminAlertService $alertService;
    private PaymentRefundEmailService $emailService;
    private BookingEnrollmentStateService $enrollmentState;

    /**
     * Construct.
     *
     * @param mixed $tableLocator Tablelocator.
     * @param mixed $gateway Gateway.
     * @param mixed $alertService Alertservice.
     * @param mixed $emailService Emailservice.
     * @return mixed
     */
    public function __construct(
        ?LocatorInterface $tableLocator = null,
        ?StripeRefundGatewayInterface $gateway = null,
        ?PaymentAdminAlertService $alertService = null,
        ?PaymentRefundEmailService $emailService = null,
    ) {
        $locator = $tableLocator ?? FactoryLocator::get('Table');
        $this->paymentsTable = $locator->get('Payments');
        $this->refundsTable = $locator->get('PaymentRefunds');
        $this->bookingsTable = $locator->get('Bookings');
        $this->gateway = $gateway ?? $this->buildGateway();
        $this->alertService = $alertService ?? new PaymentAdminAlertService($locator);
        $this->emailService = $emailService ?? new PaymentRefundEmailService($locator);
        $this->enrollmentState = new BookingEnrollmentStateService($locator);
    }

    /**
     * Issue refund.
     *
     * @param mixed $paymentId Paymentid.
     * @param mixed $amount Amount.
     * @param mixed $reason Reason.
     * @param mixed $adminId Adminid.
     */
    public function issueRefund(int $paymentId, float $amount, string $reason, ?int $adminId = null): object
    {
        $payment = $this->loadPaymentForUpdate($paymentId);
        $this->assertRefundable($payment, $amount);

        $payload = [
            'amount' => (int)round($amount * 100),
            'reason' => $this->normalizeStripeReason($reason),
            'metadata' => [
                'payment_id' => (string)$payment->payment_id,
                'booking_id' => (string)$payment->booking_id,
                'initiated_by_admin_id' => $adminId !== null ? (string)$adminId : '',
            ],
        ];

        $paymentIntentId = $this->paymentIntentIdForPayment($payment);
        $chargeId = $this->chargeIdForPayment($payment);
        if ($paymentIntentId !== '') {
            $payload['payment_intent'] = $paymentIntentId;
        } elseif ($chargeId !== '') {
            $payload['charge'] = $chargeId;
        } else {
            throw new RuntimeException(
                'This payment does not have a Stripe PaymentIntent or charge reference to refund.',
            );
        }

        try {
            $refund = $this->gateway->createRefund($payload);
        } catch (Throwable $exception) {
            $this->recordFailedRefund($payment, $amount, $reason, $adminId, $exception->getMessage());
            $this->alertService->alert(
                'Stripe refund failed',
                sprintf('Refund for payment #%d could not be submitted to Stripe.', (int)$payment->payment_id),
                [
                    'payment_id' => (int)$payment->payment_id,
                    'booking_id' => (int)$payment->booking_id,
                    'amount' => $amount,
                    'error' => $exception->getMessage(),
                ],
            );

            throw new RuntimeException(
                'Refund could not be submitted to Stripe. Please review the payment and try again.',
            );
        }

        return $this->syncRefundObject($refund, $payment, $adminId, $reason);
    }

    /**
     * Sync refund object.
     *
     * @param mixed $refund Refund.
     * @param mixed $knownPayment Knownpayment.
     * @param mixed $adminId Adminid.
     * @param mixed $fallbackReason Fallbackreason.
     */
    public function syncRefundObject(object $refund, ?object $knownPayment = null, ?int $adminId = null, ?string $fallbackReason = null): object
    {
        $stripeRefundId = (string)($refund->id ?? '');
        if ($stripeRefundId === '') {
            throw new RuntimeException(
                'Stripe refund id is missing.',
            );
        }

        $payment = $knownPayment ?? $this->findPaymentForRefund($refund);
        if ($payment === null) {
            Log::warning('Stripe refund could not be matched to a local payment: ' . json_encode([
                'stripe_refund_id' => $stripeRefundId,
                'charge' => (string)($refund->charge ?? ''),
                'payment_intent' => (string)($refund->payment_intent ?? ''),
            ]));

            $this->alertService->alert(
                'Unmatched Stripe refund',
                sprintf('Stripe refund %s could not be matched to a local payment.', $stripeRefundId),
                [
                    'stripe_refund_id' => $stripeRefundId,
                    'charge' => (string)($refund->charge ?? ''),
                    'payment_intent' => (string)($refund->payment_intent ?? ''),
                ],
            );

            return $this->refundsTable->newEntity([
                'stripe_refund_id' => $stripeRefundId,
                'amount' => round((int)($refund->amount ?? 0) / 100, 2),
                'currency_code' => strtoupper((string)($refund->currency ?? 'AUD')),
                'status' => $this->normalizeRefundStatus((string)($refund->status ?? 'pending')),
            ]);
        }

        $amount = round((int)($refund->amount ?? 0) / 100, 2);
        $status = $this->normalizeRefundStatus((string)($refund->status ?? 'pending'));
        $record = $this->refundsTable->find()
            ->where(['PaymentRefunds.stripe_refund_id' => $stripeRefundId])
            ->first();
        if ($record === null) {
            $record = $this->refundsTable->newEmptyEntity();
        }

        $record = $this->refundsTable->patchEntity($record, [
            'payment_id' => (int)$payment->payment_id,
            'stripe_refund_id' => $stripeRefundId,
            'stripe_charge_id' => (string)($refund->charge ?? $payment->stripe_charge_id ?? ''),
            'stripe_payment_intent_id' => (string)($refund->payment_intent ?? $payment->stripe_payment_intent_id ?? ''),
            'amount' => $amount,
            'currency_code' => strtoupper((string)($refund->currency ?? $payment->currency_code ?? 'AUD')),
            'status' => $status,
            'reason' => (string)($refund->reason ?? $fallbackReason ?? ''),
            'initiated_by_admin_id' => $record->initiated_by_admin_id ?? $adminId,
            'failure_message' => $status === 'failed' ? (string)($refund->failure_reason ?? 'Stripe refund failed.') : null,
            'raw_payload' => json_encode($refund, JSON_UNESCAPED_SLASHES),
        ]);
        $this->refundsTable->saveOrFail($record);

        $this->refreshPaymentRefundState((int)$payment->payment_id);
        if ($status === 'succeeded') {
            $this->emailService->sendRefundProcessed((int)$payment->payment_id, (int)$record->refund_record_id);
        }

        return $record;
    }

    /**
     * Sync charge refunds.
     *
     * @param mixed $charge Charge.
     */
    public function syncChargeRefunds(object $charge): int
    {
        $refunds = $charge->refunds->data ?? [];
        if (!is_iterable($refunds)) {
            return 0;
        }

        $count = 0;
        foreach ($refunds as $refund) {
            if (!is_object($refund)) {
                continue;
            }
            if (!isset($refund->charge)) {
                $refund->charge = (string)($charge->id ?? '');
            }
            if (!isset($refund->payment_intent)) {
                $refund->payment_intent = (string)($charge->payment_intent ?? '');
            }
            $this->syncRefundObject($refund);
            $count++;
        }

        return $count;
    }

    /**
     * Refresh payment refund state.
     *
     * @param mixed $paymentId Paymentid.
     */
    public function refreshPaymentRefundState(int $paymentId): void
    {
        $payment = $this->paymentsTable->get($paymentId);
        $refundedAmount = (float)$this->refundsTable->find()
            ->select(['total' => $this->refundsTable->find()->func()->sum('PaymentRefunds.amount')])
            ->where([
                'PaymentRefunds.payment_id' => $paymentId,
                'PaymentRefunds.status' => 'succeeded',
            ])
            ->first()
            ?->total;

        $payment->refunded_amount = round($refundedAmount, 2);
        if ($payment->refunded_amount <= 0) {
            $this->paymentsTable->saveOrFail($payment);

            return;
        }

        if ((float)$payment->refunded_amount >= round((float)$payment->amount, 2)) {
            $payment->payment_status = 'refunded';
        } else {
            $payment->payment_status = 'partially_refunded';
        }
        $payment->notes = PaymentNotes::merge($payment->notes, [
            'manual_review_required' => false,
            'refund_required' => false,
            'reason_code' => $payment->payment_status === 'refunded' ? 'payment_fully_refunded' : 'payment_partially_refunded',
            'review_state' => 'resolved_by_refund',
            'review_resolved_at' => DateTime::now()->i18nFormat(DateTime::ATOM),
        ]);
        $this->paymentsTable->saveOrFail($payment);

        if ((string)$payment->payment_status === 'refunded') {
            $this->releaseBookingAfterFullRefund((int)$payment->booking_id);
        }
    }

    /**
     * Assert refundable.
     *
     * @param mixed $payment Payment.
     * @param mixed $amount Amount.
     */
    private function assertRefundable(object $payment, float $amount): void
    {
        if (!in_array((string)$payment->payment_status, ['paid', 'partially_refunded', 'refund_required', 'disputed'], true)) {
            throw new RuntimeException(
                'Only paid payments can be refunded.',
            );
        }

        if ($amount <= 0) {
            throw new RuntimeException(
                'Refund amount must be greater than 0.',
            );
        }

        $remaining = round((float)$payment->amount - (float)$payment->refunded_amount, 2);
        if ($amount > $remaining) {
            throw new RuntimeException(
                'Refund amount cannot exceed the remaining refundable balance.',
            );
        }
    }

    /**
     * Record failed refund.
     *
     * @param mixed $payment Payment.
     * @param mixed $amount Amount.
     * @param mixed $reason Reason.
     * @param mixed $adminId Adminid.
     * @param mixed $message Message.
     */
    private function recordFailedRefund(object $payment, float $amount, string $reason, ?int $adminId, string $message): void
    {
        $record = $this->refundsTable->newEntity([
            'payment_id' => (int)$payment->payment_id,
            'stripe_charge_id' => (string)($payment->stripe_charge_id ?? ''),
            'stripe_payment_intent_id' => $this->paymentIntentIdForPayment($payment),
            'amount' => $amount,
            'currency_code' => (string)($payment->currency_code ?? 'AUD'),
            'status' => 'failed',
            'reason' => $reason,
            'initiated_by_admin_id' => $adminId,
            'failure_message' => $message,
        ]);
        $this->refundsTable->saveOrFail($record);
    }

    /**
     * Release booking after full refund.
     *
     * @param mixed $bookingId Bookingid.
     */
    private function releaseBookingAfterFullRefund(int $bookingId): void
    {
        $booking = $this->enrollmentState->getBookingWithPayments($bookingId);
        if ($booking === null || !$this->enrollmentState->bookingCanBeReused($booking)) {
            return;
        }

        if (!in_array((string)$booking->booking_status, ['pending', 'confirmed', 'completed'], true)) {
            return;
        }

        $booking->booking_status = 'cancelled';
        $booking->updated_at = DateTime::now();
        $booking->reminder_sent_at = null;
        $booking->booking_confirmation_sent_at = null;
        $this->bookingsTable->saveOrFail($booking);
    }

    /**
     * Find payment for refund.
     *
     * @param mixed $refund Refund.
     */
    private function findPaymentForRefund(object $refund): ?object
    {
        $chargeId = (string)($refund->charge ?? '');
        $paymentIntentId = (string)($refund->payment_intent ?? '');

        $query = $this->paymentsTable->find();
        if ($chargeId !== '' && $paymentIntentId !== '') {
            $query->where([
                'OR' => [
                    ['Payments.stripe_charge_id' => $chargeId],
                    ['Payments.stripe_payment_intent_id' => $paymentIntentId],
                ],
            ]);
        } elseif ($chargeId !== '') {
            $query->where(['Payments.stripe_charge_id' => $chargeId]);
        } elseif ($paymentIntentId !== '') {
            $query->where(['Payments.stripe_payment_intent_id' => $paymentIntentId]);
        } else {
            return null;
        }

        return $query->first();
    }

    /**
     * Payment intent id for payment.
     *
     * @param mixed $payment Payment.
     */
    private function paymentIntentIdForPayment(object $payment): string
    {
        $value = (string)($payment->stripe_payment_intent_id ?? '');
        if ($value !== '') {
            return $value;
        }

        $notes = json_decode((string)($payment->notes ?? ''), true);
        if (is_array($notes) && is_string($notes['payment_intent'] ?? null)) {
            return $notes['payment_intent'];
        }

        return '';
    }

    /**
     * Charge id for payment.
     *
     * @param mixed $payment Payment.
     */
    private function chargeIdForPayment(object $payment): string
    {
        return (string)($payment->stripe_charge_id ?? '');
    }

    /**
     * Normalize stripe reason.
     *
     * @param mixed $reason Reason.
     */
    private function normalizeStripeReason(string $reason): string
    {
        return in_array($reason, ['duplicate', 'fraudulent', 'requested_by_customer'], true)
            ? $reason
            : 'requested_by_customer';
    }

    /**
     * Normalize refund status.
     *
     * @param mixed $status Status.
     */
    private function normalizeRefundStatus(string $status): string
    {
        return in_array($status, ['pending', 'succeeded', 'failed', 'canceled', 'requires_action'], true)
            ? $status
            : 'pending';
    }

    /**
     * Load payment for update.
     *
     * @param mixed $paymentId Paymentid.
     */
    private function loadPaymentForUpdate(int $paymentId): object
    {
        $query = $this->paymentsTable->find()
            ->where(['Payments.payment_id' => $paymentId]);

        if ($this->paymentsTable->getConnection()->getDriver() instanceof Mysql) {
            $query->epilog('FOR UPDATE');
        }

        return $query->firstOrFail();
    }

    /**
     * Build gateway.
     */
    private function buildGateway(): StripeRefundGatewayInterface
    {
        $gatewayClass = (string)Configure::read('Payments.refund_gateway_class', StripeRefundGateway::class);
        $gateway = new $gatewayClass();

        if (!$gateway instanceof StripeRefundGatewayInterface) {
            throw new RuntimeException(sprintf(
                'Configured refund gateway "%s" must implement %s.',
                $gatewayClass,
                StripeRefundGatewayInterface::class,
            ));
        }

        return $gateway;
    }
}
