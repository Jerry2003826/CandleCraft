<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Datasource\FactoryLocator;
use Cake\ORM\Locator\LocatorInterface;

class BookingEnrollmentStateService
{
    public const CAPACITY_BOOKING_STATUSES = ['pending', 'confirmed'];
    public const ENROLLED_BOOKING_STATUSES = ['pending', 'confirmed', 'completed'];

    private const BLOCKING_PAYMENT_STATUSES = [
        'pending',
        'paid',
        'refund_required',
        'partially_refunded',
        'disputed',
    ];

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
     * Count blocking bookings for class.
     *
     * @param mixed $classId Classid.
     * @param mixed $statuses Statuses.
     */
    public function countBlockingBookingsForClass(int $classId, array $statuses = self::CAPACITY_BOOKING_STATUSES): int
    {
        $count = 0;
        foreach ($this->matchingBookings(['Bookings.class_id' => $classId], $statuses) as $booking) {
            if ($this->bookingBlocksEnrollment($booking)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Has blocking booking for student.
     *
     * @param mixed $classId Classid.
     * @param mixed $studentId Studentid.
     * @param mixed $statuses Statuses.
     */
    public function hasBlockingBookingForStudent(
        int $classId,
        int $studentId,
        array $statuses = self::ENROLLED_BOOKING_STATUSES,
    ): bool {
        return $this->findBlockingBookingForStudent($classId, $studentId, $statuses) !== null;
    }

    /**
     * Has blocking booking for students.
     *
     * @param mixed $classId Classid.
     * @param mixed $studentIds Studentids.
     * @param mixed $statuses Statuses.
     */
    public function hasBlockingBookingForStudents(
        int $classId,
        array $studentIds,
        array $statuses = self::ENROLLED_BOOKING_STATUSES,
    ): bool {
        if ($studentIds === []) {
            return false;
        }

        foreach (
            $this->matchingBookings([
            'Bookings.class_id' => $classId,
            'Bookings.student_id IN' => $studentIds,
            ], $statuses) as $booking
        ) {
            if ($this->bookingBlocksEnrollment($booking)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Find blocking booking for student.
     *
     * @param mixed $classId Classid.
     * @param mixed $studentId Studentid.
     * @param mixed $statuses Statuses.
     */
    public function findBlockingBookingForStudent(
        int $classId,
        int $studentId,
        array $statuses = self::ENROLLED_BOOKING_STATUSES,
    ): ?object {
        foreach (
            $this->matchingBookings([
            'Bookings.class_id' => $classId,
            'Bookings.student_id' => $studentId,
            ], $statuses) as $booking
        ) {
            if ($this->bookingBlocksEnrollment($booking)) {
                return $booking;
            }
        }

        return null;
    }

    /**
     * Booking blocks enrollment.
     *
     * @param mixed $booking Booking.
     */
    public function bookingBlocksEnrollment(object $booking): bool
    {
        $payments = $booking->payments ?? [];
        if (!is_iterable($payments)) {
            return true;
        }

        $hasPayment = false;
        foreach ($payments as $payment) {
            $hasPayment = true;
            if (in_array((string)($payment->payment_status ?? ''), self::BLOCKING_PAYMENT_STATUSES, true)) {
                return true;
            }
        }

        return !$hasPayment;
    }

    /**
     * Booking can be reused.
     *
     * @param mixed $booking Booking.
     */
    public function bookingCanBeReused(object $booking): bool
    {
        return !$this->bookingBlocksEnrollment($booking);
    }

    /**
     * Get booking with payments.
     *
     * @param mixed $bookingId Bookingid.
     */
    public function getBookingWithPayments(int $bookingId): ?object
    {
        return $this->bookingsTable->find()
            ->contain(['Payments'])
            ->where(['Bookings.booking_id' => $bookingId])
            ->first();
    }

    /**
     * Matching bookings.
     *
     * @param mixed $conditions Conditions.
     * @param mixed $statuses Statuses.
     */
    private function matchingBookings(array $conditions, array $statuses): iterable
    {
        return $this->bookingsTable->find()
            ->contain(['Payments'])
            ->where($conditions + ['Bookings.booking_status IN' => $statuses])
            ->orderBy([
                'Bookings.updated_at' => 'DESC',
                'Bookings.booking_id' => 'DESC',
            ])
            ->all();
    }
}
