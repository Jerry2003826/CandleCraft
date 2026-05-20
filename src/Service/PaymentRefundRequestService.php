<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Database\Driver\Mysql;
use Cake\Datasource\FactoryLocator;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorInterface;
use RuntimeException;

class PaymentRefundRequestService
{
    private object $paymentsTable;
    private PaymentRefundEmailService $emailService;

    /**
     * Construct.
     *
     * @param mixed $tableLocator Tablelocator.
     * @param mixed $emailService Emailservice.
     * @return mixed
     */
    public function __construct(
        ?LocatorInterface $tableLocator = null,
        ?PaymentRefundEmailService $emailService = null,
    ) {
        $locator = $tableLocator ?? FactoryLocator::get('Table');
        $this->paymentsTable = $locator->get('Payments');
        $this->emailService = $emailService ?? new PaymentRefundEmailService($locator);
    }

    /**
     * Request refund.
     *
     * @param mixed $paymentId Paymentid.
     * @param mixed $context Context.
     */
    public function requestRefund(int $paymentId, array $context): object
    {
        $payment = $this->loadPaymentForUpdate($paymentId);
        $status = (string)$payment->payment_status;

        if ($status === 'refund_required') {
            $this->emailService->sendRequestReceived($paymentId, $context);

            return $payment;
        }

        if (!in_array($status, ['paid', 'partially_refunded'], true)) {
            throw new RuntimeException('Only paid payments can be submitted for a refund request.');
        }

        $remaining = round((float)$payment->amount - (float)$payment->refunded_amount, 2);
        if ($remaining <= 0) {
            throw new RuntimeException('This payment has no remaining refundable balance.');
        }

        $payment->payment_status = 'refund_required';
        $payment->notes = PaymentNotes::merge($payment->notes, [
            'manual_review_required' => true,
            'refund_required' => true,
            'refund_requested_by_customer' => true,
            'refund_requested_by_user_id' => (int)($context['requested_by_user_id'] ?? 0),
            'refund_request_portal' => (string)($context['portal_source'] ?? 'unknown'),
            'refund_requested_at' => DateTime::now()->i18nFormat(DateTime::ATOM),
            'reason_code' => 'customer_refund_requested',
            'previous_payment_status' => $status,
            'review_state' => 'awaiting_admin_refund_review',
        ]);

        $this->paymentsTable->saveOrFail($payment);
        $this->emailService->sendRequestReceived($paymentId, $context);

        return $payment;
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
}
