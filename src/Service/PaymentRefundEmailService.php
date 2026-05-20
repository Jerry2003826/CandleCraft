<?php
declare(strict_types=1);

namespace App\Service;

use App\Mailer\PaymentRefundMailer;
use Cake\Datasource\FactoryLocator;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorInterface;
use DateTimeInterface;
use Throwable;

class PaymentRefundEmailService
{
    private object $paymentsTable;
    private object $refundsTable;

    /**
     * Construct.
     *
     * @param mixed $tableLocator Tablelocator.
     * @return mixed
     */
    public function __construct(?LocatorInterface $tableLocator = null)
    {
        $locator = $tableLocator ?? FactoryLocator::get('Table');
        $this->paymentsTable = $locator->get('Payments');
        $this->refundsTable = $locator->get('PaymentRefunds');
    }

    /**
     * Send request received.
     *
     * @param mixed $paymentId Paymentid.
     * @param mixed $context Context.
     */
    public function sendRequestReceived(int $paymentId, array $context = []): bool
    {
        $payment = $this->loadPayment($paymentId);
        if (!$payment) {
            return false;
        }

        $notes = $this->decodeNotes((string)($payment->notes ?? ''));
        if (!empty($notes['refund_request_email_sent_at'])) {
            return false;
        }

        $recipient = $this->resolveRecipient($payment, $context);
        if ($recipient['email'] === '') {
            Log::warning('Refund request email skipped: no recipient email.', [
                'payment_id' => $paymentId,
                'booking_id' => $payment->booking_id ?? null,
            ]);

            return false;
        }

        try {
            (new PaymentRefundMailer('default'))
                ->requestReceived($this->buildPayload($payment, $recipient))
                ->deliver();
        } catch (Throwable $exception) {
            Log::warning('Refund request email failed.', [
                'payment_id' => $paymentId,
                'booking_id' => $payment->booking_id ?? null,
                'recipient' => $recipient['email'],
                'error' => $exception->getMessage(),
            ]);

            return false;
        }

        $payment->notes = PaymentNotes::merge((string)($payment->notes ?? ''), [
            'refund_request_email_sent_at' => DateTime::now()->format('Y-m-d H:i:s'),
            'refund_request_email_recipient' => $recipient['email'],
        ]);
        $this->paymentsTable->save($payment);

        return true;
    }

    /**
     * Send refund processed.
     *
     * @param mixed $paymentId Paymentid.
     * @param mixed $refundRecordId Refundrecordid.
     */
    public function sendRefundProcessed(int $paymentId, int $refundRecordId): bool
    {
        $payment = $this->loadPayment($paymentId);
        if (!$payment) {
            return false;
        }

        $refund = $this->refundsTable->find()
            ->where([
                'PaymentRefunds.refund_record_id' => $refundRecordId,
                'PaymentRefunds.payment_id' => $paymentId,
                'PaymentRefunds.status' => 'succeeded',
            ])
            ->first();

        if (!$refund) {
            return false;
        }

        $notes = $this->decodeNotes((string)($payment->notes ?? ''));
        $sentKey = 'refund_processed_email_sent_' . $refundRecordId;
        if (!empty($notes[$sentKey])) {
            return false;
        }

        $recipient = $this->resolveRecipient($payment, []);
        if ($recipient['email'] === '') {
            Log::warning('Refund processed email skipped: no recipient email.', [
                'payment_id' => $paymentId,
                'refund_record_id' => $refundRecordId,
            ]);

            return false;
        }

        try {
            (new PaymentRefundMailer('default'))
                ->refundProcessed($this->buildPayload($payment, $recipient, $refund))
                ->deliver();
        } catch (Throwable $exception) {
            Log::warning('Refund processed email failed.', [
                'payment_id' => $paymentId,
                'refund_record_id' => $refundRecordId,
                'recipient' => $recipient['email'],
                'error' => $exception->getMessage(),
            ]);

            return false;
        }

        $payment->notes = PaymentNotes::merge((string)($payment->notes ?? ''), [
            $sentKey => DateTime::now()->format('Y-m-d H:i:s'),
            'refund_processed_email_recipient' => $recipient['email'],
        ]);
        $this->paymentsTable->save($payment);

        return true;
    }

    /**
     * Load payment.
     *
     * @param mixed $paymentId Paymentid.
     */
    private function loadPayment(int $paymentId): ?object
    {
        return $this->paymentsTable->find()
            ->contain([
                'Bookings' => [
                    'Students' => ['Users'],
                    'Parents' => ['Users'],
                    'Classes' => ['Courses', 'Teachers'],
                ],
            ])
            ->where(['Payments.payment_id' => $paymentId])
            ->first();
    }

    /**
     * @return array{email: string, name: string}
     */
    private function resolveRecipient(object $payment, array $context): array
    {
        $email = trim((string)($context['recipient_email'] ?? ''));
        $name = trim((string)($context['recipient_name'] ?? ''));
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'email' => $email,
                'name' => $name,
            ];
        }

        $booking = $payment->booking ?? null;
        $parentUser = $booking?->parent?->user ?? null;
        if ($parentUser && filter_var((string)($parentUser->email ?? ''), FILTER_VALIDATE_EMAIL)) {
            return [
                'email' => (string)$parentUser->email,
                'name' => (string)($booking?->parent?->parent_name ?? $parentUser->username ?? ''),
            ];
        }

        $student = $booking?->student ?? null;
        $studentUser = $student?->user ?? null;
        if ($studentUser && filter_var((string)($studentUser->email ?? ''), FILTER_VALIDATE_EMAIL)) {
            return [
                'email' => (string)$studentUser->email,
                'name' => (string)($student?->student_name ?? $studentUser->username ?? ''),
            ];
        }

        return [
            'email' => '',
            'name' => '',
        ];
    }

    /**
     * Build payload.
     *
     * @param mixed $payment Payment.
     * @param mixed $recipient Recipient.
     * @param mixed $refund Refund.
     */
    private function buildPayload(object $payment, array $recipient, ?object $refund = null): array
    {
        $booking = $payment->booking ?? null;
        $class = $booking?->class_entity ?? null;
        $course = $class?->course ?? null;
        $teacher = $class?->teacher ?? null;
        $student = $booking?->student ?? null;
        $currencyCode = (string)($payment->currency_code ?? $refund?->currency_code ?? 'AUD');
        $paymentAmount = (float)($payment->amount ?? 0);
        $refundedAmount = (float)($refund?->amount ?? max(0, $paymentAmount - (float)($payment->refunded_amount ?? 0)));

        return [
            'recipient_name' => $recipient['name'],
            'recipient_email' => $recipient['email'],
            'student_name' => (string)($student?->student_name ?? ''),
            'class_name' => (string)($course?->course_name ?? $class?->class_code ?? 'Class booking'),
            'class_code' => (string)($class?->class_code ?? ''),
            'schedule' => $this->formatDate($class?->start_datetime) ?: 'TBA',
            'location' => (string)($class?->location ?? 'TBA'),
            'teacher_name' => (string)($teacher?->teacher_name ?? 'Your teacher'),
            'booking_id' => (int)($booking?->booking_id ?? $payment->booking_id ?? 0),
            'payment_id' => (int)($payment->payment_id ?? 0),
            'payment_amount' => $paymentAmount,
            'refund_amount' => $refundedAmount,
            'currency_code' => $currencyCode,
            'payment_amount_formatted' => $currencyCode . ' ' . number_format($paymentAmount, 2),
            'refund_amount_formatted' => $currencyCode . ' ' . number_format($refundedAmount, 2),
            'payment_reference' => (string)($payment->transaction_reference ?? $payment->stripe_session_id ?? $payment->payment_id ?? ''),
            'refund_reference' => (string)($refund?->stripe_refund_id ?? ''),
            'refund_date' => $this->formatDate($refund?->created_at ?? null) ?: DateTime::now()->format('D j M Y, g:ia'),
            'business_days' => '5-10 business days',
        ];
    }

    /**
     * Format date.
     *
     * @param mixed $date Date.
     */
    private function formatDate(mixed $date): string
    {
        if ($date instanceof DateTimeInterface) {
            return $date->format('D j M Y, g:ia');
        }

        if ($date === null || $date === '') {
            return '';
        }

        $timestamp = strtotime((string)$date);

        return $timestamp ? date('D j M Y, g:ia', $timestamp) : '';
    }

    /**
     * Decode notes.
     *
     * @param mixed $notes Notes.
     */
    private function decodeNotes(string $notes): array
    {
        if ($notes === '') {
            return [];
        }

        $decoded = json_decode($notes, true);

        return is_array($decoded) ? $decoded : [];
    }
}
