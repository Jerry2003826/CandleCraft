<?php
declare(strict_types=1);

namespace App\Service;

use App\Mailer\BookingPaymentMailer;
use Cake\Datasource\FactoryLocator;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorInterface;
use DateTimeInterface;
use Throwable;

class PaymentReceiptEmailService
{
    private object $paymentsTable;

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
    }

    /**
     * Send for payment.
     *
     * @param mixed $paymentId Paymentid.
     * @param mixed $context Context.
     */
    public function sendForPayment(int $paymentId, array $context = []): bool
    {
        $payment = $this->paymentsTable->find()
            ->contain([
                'Bookings' => [
                    'Students' => ['Users'],
                    'Parents' => ['Users'],
                    'Classes' => ['Courses', 'Teachers'],
                ],
            ])
            ->where(['Payments.payment_id' => $paymentId])
            ->first();

        if (!$payment || (string)($payment->payment_status ?? '') !== 'paid') {
            return false;
        }

        $notes = $this->decodeNotes((string)($payment->notes ?? ''));
        if (!empty($notes['receipt_email_sent_at'])) {
            return false;
        }

        $recipient = $this->resolveRecipient($payment, $context);
        if ($recipient['email'] === '') {
            Log::warning('Payment receipt email skipped: no recipient email.', [
                'payment_id' => $paymentId,
                'booking_id' => $payment->booking_id ?? null,
            ]);

            return false;
        }

        try {
            $mailer = new BookingPaymentMailer('default');
            $mailer->send('paymentReceipt', [$this->buildPayload($payment, $recipient, $context)]);
        } catch (Throwable $exception) {
            Log::warning('Payment receipt email failed.', [
                'payment_id' => $paymentId,
                'booking_id' => $payment->booking_id ?? null,
                'recipient' => $recipient['email'],
                'error' => $exception->getMessage(),
            ]);

            return false;
        }

        $payment->notes = PaymentNotes::merge((string)($payment->notes ?? ''), [
            'receipt_email_sent_at' => DateTime::now()->format('Y-m-d H:i:s'),
            'receipt_email_recipient' => $recipient['email'],
        ]);

        if (!$this->paymentsTable->save($payment)) {
            Log::warning('Payment receipt email was sent but could not be marked as sent.', [
                'payment_id' => $paymentId,
                'booking_id' => $payment->booking_id ?? null,
                'recipient' => $recipient['email'],
            ]);
        }

        return true;
    }

    /**
     * @return array{email: string, name: string}
     */
    private function resolveRecipient(object $payment, array $context): array
    {
        $booking = $payment->booking ?? null;
        $parentUser = $booking?->parent?->user ?? null;
        if ($parentUser && filter_var((string)($parentUser->email ?? ''), FILTER_VALIDATE_EMAIL)) {
            return [
                'email' => (string)$parentUser->email,
                'name' => (string)($booking?->parent?->parent_name ?? $parentUser->username ?? ''),
            ];
        }

        $email = trim((string)($context['recipient_email'] ?? ''));
        $name = trim((string)($context['recipient_name'] ?? ''));
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'email' => $email,
                'name' => $name,
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
     * @param mixed $context Context.
     */
    private function buildPayload(object $payment, array $recipient, array $context): array
    {
        $booking = $payment->booking ?? null;
        $class = $booking?->class_entity ?? null;
        $course = $class?->course ?? null;
        $teacher = $class?->teacher ?? null;
        $student = $booking?->student ?? null;
        $amount = (float)($payment->amount ?? 0);
        $currencyCode = (string)($payment->currency_code ?? 'AUD');
        $amountFormatted = $currencyCode . ' ' . number_format($amount, 2);
        $className = (string)($course?->course_name ?? $class?->class_code ?? 'Class booking');
        $classCode = (string)($class?->class_code ?? '');
        $studentName = (string)($student?->student_name ?? '');
        $schedule = $this->formatDate($class?->start_datetime) ?: 'TBA';
        $location = (string)($class?->location ?? 'TBA');
        $teacherName = (string)($teacher?->teacher_name ?? 'Your teacher');
        $paymentReference = (string)($payment->transaction_reference ?? $payment->payment_id ?? '');
        $paymentDate = $this->formatDate($payment->payment_date ?? null) ?: DateTime::now()->format('D j M Y, g:ia');
        $bookingId = (int)($booking?->booking_id ?? $payment->booking_id ?? 0);
        $snapshotContentId = 'receipt-snapshot-' . (int)($payment->payment_id ?? $bookingId);

        $payload = [
            'recipient_name' => $recipient['name'],
            'recipient_email' => $recipient['email'],
            'student_name' => $studentName,
            'class_name' => $className,
            'class_code' => $classCode,
            'schedule' => $schedule,
            'location' => $location,
            'teacher_name' => $teacherName,
            'amount' => $amount,
            'currency_code' => $currencyCode,
            'amount_formatted' => $amountFormatted,
            'payment_reference' => $paymentReference,
            'payment_date' => $paymentDate,
            'booking_id' => $bookingId,
            'receipt_snapshot_content_id' => $snapshotContentId,
            'receipt_snapshot_filename' => sprintf('candlecraft-receipt-%d.svg', $bookingId ?: (int)$payment->payment_id),
        ];

        $payload['receipt_snapshot_svg'] = $this->buildReceiptSnapshotSvg($payload);

        return $payload;
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

    /**
     * Build receipt snapshot svg.
     *
     * @param mixed $payload Payload.
     */
    private function buildReceiptSnapshotSvg(array $payload): string
    {
        $classLine = $payload['class_name'];
        if ($payload['class_code'] !== '') {
            $classLine .= ' (' . $payload['class_code'] . ')';
        }

        $rows = [
            ['Course', $classLine],
            ['Student', $payload['student_name'] ?: 'Student'],
            ['Date and time', $payload['schedule']],
            ['Location', $payload['location']],
            ['Teacher', $payload['teacher_name']],
            ['Booking number', '#' . (string)$payload['booking_id']],
            ['Payment date', $payload['payment_date']],
            ['Payment reference', $payload['payment_reference']],
        ];

        $rowSvg = '';
        $y = 184;
        foreach ($rows as [$label, $value]) {
            $rowSvg .= sprintf(
                '<text x="52" y="%d" fill="#7a6049" font-size="15" font-weight="700">%s</text>'
                . '<text x="224" y="%d" fill="#2f2219" font-size="15">%s</text>',
                $y,
                $this->svgText($label),
                $y,
                $this->svgText($this->truncateForSvg((string)$value, 46)),
            );
            $y += 34;
        }

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="760" height="560" viewBox="0 0 760 560">'
            . '<rect width="760" height="560" rx="24" fill="#fbf7f0"/>'
            . '<rect x="24" y="24" width="712" height="512" rx="22" fill="#ffffff" stroke="#ead7c0" stroke-width="2"/>'
            . '<text x="52" y="78" fill="#9a641f" font-family="Arial, Helvetica, sans-serif" font-size="15"'
            . ' font-weight="700" letter-spacing="3">CANDLECRAFT ACADEMY</text>'
            . '<text x="52" y="125" fill="#2f2219" font-family="Georgia, serif" font-size="34" font-weight="700">Payment Receipt</text>'
            . '<rect x="52" y="150" width="656" height="1" fill="#ead7c0"/>'
            . '<g font-family="Arial, Helvetica, sans-serif">%s</g>'
            . '<rect x="52" y="462" width="656" height="54" rx="14" fill="#2f2219"/>'
            . '<text x="80" y="497" fill="#f7ead8" font-family="Arial, Helvetica, sans-serif" font-size="17" font-weight="700">Amount paid</text>'
            . '<text x="690" y="497" text-anchor="end" fill="#f7ead8" font-family="Arial, Helvetica, sans-serif" font-size="24" font-weight="700">%s</text>'
            . '</svg>',
            $rowSvg,
            $this->svgText((string)$payload['amount_formatted']),
        );
    }

    /**
     * Svg text.
     *
     * @param mixed $value Value.
     */
    private function svgText(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Truncate for svg.
     *
     * @param mixed $value Value.
     * @param mixed $limit Limit.
     */
    private function truncateForSvg(string $value, int $limit): string
    {
        preg_match_all('/./u', $value, $matches);
        $characters = $matches[0] ?? [];

        if (count($characters) <= $limit) {
            return $value;
        }

        return implode('', array_slice($characters, 0, max(0, $limit - 3))) . '...';
    }
}
