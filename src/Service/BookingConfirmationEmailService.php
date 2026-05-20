<?php
declare(strict_types=1);

namespace App\Service;

use App\Mailer\BookingConfirmationMailer;
use Cake\Datasource\FactoryLocator;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorInterface;
use Cake\Routing\Router;
use DateTimeInterface;
use Throwable;

class BookingConfirmationEmailService
{
    private object $bookingsTable;

    /**
     * Construct.
     *
     * @param mixed $tableLocator Tablelocator.
     * @return mixed
     */
    public function __construct(?LocatorInterface $tableLocator = null)
    {
        $locator = $tableLocator ?? FactoryLocator::get('Table');
        $this->bookingsTable = $locator->get('Bookings');
    }

    /**
     * Send for booking.
     *
     * @param mixed $bookingId Bookingid.
     * @param mixed $context Context.
     */
    public function sendForBooking(int $bookingId, array $context = []): bool
    {
        $booking = $this->bookingsTable->find()
            ->contain([
                'Students' => ['Users'],
                'Parents' => ['Users'],
                'Classes' => ['Courses', 'Teachers'],
            ])
            ->where(['Bookings.booking_id' => $bookingId])
            ->first();

        if (!$booking || !in_array((string)($booking->booking_status ?? ''), ['pending', 'confirmed', 'completed'], true)) {
            return false;
        }

        if (!empty($booking->booking_confirmation_sent_at)) {
            return false;
        }

        $recipient = $this->resolveRecipient($booking, $context);
        if ($recipient['email'] === '') {
            Log::warning('Booking confirmation email skipped: no recipient email.', [
                'booking_id' => $bookingId,
                'student_id' => $booking->student_id ?? null,
                'parent_id' => $booking->parent_id ?? null,
            ]);

            return false;
        }

        try {
            $mailer = new BookingConfirmationMailer('default');
            $mailer->send('bookingConfirmation', [$this->buildPayload($booking, $recipient, $context)]);
        } catch (Throwable $exception) {
            Log::warning('Booking confirmation email failed.', [
                'booking_id' => $bookingId,
                'recipient' => $recipient['email'],
                'error' => $exception->getMessage(),
            ]);

            return false;
        }

        $booking->booking_confirmation_sent_at = DateTime::now();
        if (!$this->bookingsTable->save($booking)) {
            Log::warning('Booking confirmation email was sent but could not be marked as sent.', [
                'booking_id' => $bookingId,
                'recipient' => $recipient['email'],
            ]);
        }

        return true;
    }

    /**
     * @return array{email: string, name: string}
     */
    private function resolveRecipient(object $booking, array $context): array
    {
        $parentUser = $booking->parent?->user ?? null;
        if ($parentUser && filter_var((string)($parentUser->email ?? ''), FILTER_VALIDATE_EMAIL)) {
            return [
                'email' => (string)$parentUser->email,
                'name' => (string)($booking->parent?->parent_name ?? $parentUser->username ?? ''),
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

        $student = $booking->student ?? null;
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
     * @param mixed $booking Booking.
     * @param mixed $recipient Recipient.
     * @param mixed $context Context.
     */
    private function buildPayload(object $booking, array $recipient, array $context): array
    {
        $class = $booking->class_entity ?? null;
        $course = $class?->course ?? null;
        $teacher = $class?->teacher ?? null;
        $student = $booking->student ?? null;
        $portalPrefix = $this->resolvePortalPrefix($booking, $context);

        return [
            'recipient_name' => $recipient['name'],
            'recipient_email' => $recipient['email'],
            'student_name' => (string)($student?->student_name ?? ''),
            'class_name' => (string)($course?->course_name ?? $class?->class_code ?? 'Class booking'),
            'class_code' => (string)($class?->class_code ?? ''),
            'schedule' => $this->formatDate($class?->start_datetime) ?: 'TBA',
            'location' => (string)($class?->location ?? 'TBA'),
            'teacher_name' => (string)($teacher?->teacher_name ?? 'Your teacher'),
            'amount_due' => (float)($booking->price_at_booking ?? 0),
            'booking_id' => (int)($booking->booking_id ?? 0),
            'booking_status' => (string)($booking->booking_status ?? ''),
            'payment_url' => Router::url([
                'prefix' => $portalPrefix,
                'controller' => 'Payments',
                'action' => 'process',
                $booking->booking_id,
            ], true),
            'schedule_url' => Router::url([
                'prefix' => $portalPrefix,
                'controller' => 'Bookings',
                'action' => 'index',
            ], true),
        ];
    }

    /**
     * Resolve portal prefix.
     *
     * @param mixed $booking Booking.
     * @param mixed $context Context.
     */
    private function resolvePortalPrefix(object $booking, array $context): string
    {
        if (!empty($booking->parent_id)) {
            return 'Parent';
        }

        $prefix = (string)($context['portal_prefix'] ?? '');
        if (in_array($prefix, ['Consumer', 'Parent', 'Student'], true)) {
            return $prefix;
        }

        return 'Consumer';
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
}
