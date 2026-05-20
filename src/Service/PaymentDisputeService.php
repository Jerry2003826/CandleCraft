<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Datasource\FactoryLocator;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorInterface;
use RuntimeException;

class PaymentDisputeService
{
    private object $paymentsTable;
    private object $disputesTable;
    private PaymentAdminAlertService $alertService;

    /**
     * Construct.
     *
     * @param mixed $tableLocator Tablelocator.
     * @param mixed $alertService Alertservice.
     * @return mixed
     */
    public function __construct(?LocatorInterface $tableLocator = null, ?PaymentAdminAlertService $alertService = null)
    {
        $locator = $tableLocator ?? FactoryLocator::get('Table');
        $this->paymentsTable = $locator->get('Payments');
        $this->disputesTable = $locator->get('PaymentDisputes');
        $this->alertService = $alertService ?? new PaymentAdminAlertService($locator);
    }

    /**
     * Sync dispute.
     *
     * @param mixed $dispute Dispute.
     * @param mixed $eventType Eventtype.
     */
    public function syncDispute(object $dispute, string $eventType): object
    {
        $stripeDisputeId = (string)($dispute->id ?? '');
        if ($stripeDisputeId === '') {
            throw new RuntimeException(
                'Stripe dispute id is missing.',
            );
        }

        $record = $this->disputesTable->find()
            ->where(['PaymentDisputes.stripe_dispute_id' => $stripeDisputeId])
            ->first();
        $isNew = $record === null;
        if ($record === null) {
            $record = $this->disputesTable->newEmptyEntity();
        }

        $payment = $this->findPaymentForDispute($dispute);
        if ($payment === null) {
            Log::warning('Stripe dispute could not be matched to a local payment: ' . json_encode([
                'stripe_dispute_id' => $stripeDisputeId,
                'charge' => (string)($dispute->charge ?? ''),
                'payment_intent' => (string)($dispute->payment_intent ?? ''),
            ]));
        }

        $status = (string)($dispute->status ?? 'needs_response');
        $record = $this->disputesTable->patchEntity($record, [
            'payment_id' => $payment?->payment_id,
            'stripe_dispute_id' => $stripeDisputeId,
            'stripe_charge_id' => (string)($dispute->charge ?? ''),
            'stripe_payment_intent_id' => (string)($dispute->payment_intent ?? $payment?->stripe_payment_intent_id ?? ''),
            'amount' => round((int)($dispute->amount ?? 0) / 100, 2),
            'currency_code' => strtoupper((string)($dispute->currency ?? 'AUD')),
            'reason' => (string)($dispute->reason ?? ''),
            'status' => $status,
            'evidence_due_by' => $this->dateTimeFromTimestamp($dispute->evidence_details->due_by ?? null),
            'opened_at' => $this->dateTimeFromTimestamp($dispute->created ?? null),
            'closed_at' => str_contains($eventType, 'closed') ? DateTime::now() : ($record->closed_at ?? null),
            'raw_payload' => json_encode($dispute, JSON_UNESCAPED_SLASHES),
        ]);
        $this->disputesTable->saveOrFail($record);
        if ($payment !== null) {
            $this->applyDisputeStateToPayment($payment, $record);
        }

        if ($isNew || $eventType === 'charge.dispute.created') {
            $this->alertService->alert(
                'Stripe dispute opened',
                sprintf('A Stripe dispute requires review before its deadline: %s.', $stripeDisputeId),
                [
                    'stripe_dispute_id' => $stripeDisputeId,
                    'payment_id' => $payment?->payment_id,
                    'amount' => $record->amount,
                    'status' => $status,
                    'reason' => $record->reason,
                ],
            );
        }

        return $record;
    }

    /**
     * Find payment for dispute.
     *
     * @param mixed $dispute Dispute.
     */
    private function findPaymentForDispute(object $dispute): ?object
    {
        $chargeId = (string)($dispute->charge ?? '');
        $paymentIntentId = (string)($dispute->payment_intent ?? '');

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
     * Date time from timestamp.
     *
     * @param mixed $timestamp Timestamp.
     */
    private function dateTimeFromTimestamp(mixed $timestamp): ?DateTime
    {
        if (!is_numeric($timestamp) || (int)$timestamp <= 0) {
            return null;
        }

        return new DateTime('@' . (int)$timestamp);
    }

    /**
     * Apply dispute state to payment.
     *
     * @param mixed $payment Payment.
     * @param mixed $dispute Dispute.
     */
    private function applyDisputeStateToPayment(object $payment, object $dispute): void
    {
        $status = (string)$dispute->status;
        if (in_array((string)$payment->payment_status, ['refunded', 'partially_refunded'], true)) {
            return;
        }

        if ($status === 'won' && (string)$payment->payment_status === 'disputed') {
            $payment->payment_status = 'paid';
        } elseif (!in_array($status, ['won', 'warning_closed'], true)) {
            $payment->payment_status = 'disputed';
        }

        $payment->notes = PaymentNotes::merge($payment->notes, [
            'stripe_dispute_id' => (string)$dispute->stripe_dispute_id,
            'dispute_status' => $status,
            'reason_code' => 'stripe_dispute_' . $status,
        ]);
        $this->paymentsTable->saveOrFail($payment);
    }
}
