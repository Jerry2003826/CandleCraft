<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Database\Driver\Mysql;
use Cake\Datasource\FactoryLocator;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorInterface;
use InvalidArgumentException;
use RuntimeException;

class BookingService
{
    public const DEFAULT_ALLOWED_CLASS_STATUSES = ['scheduled'];
    public const PARENT_ALLOWED_CLASS_STATUSES = ['scheduled', 'ongoing'];

    private const BOOKABLE_CLASS_STATUSES = ['scheduled', 'ongoing'];
    private const ACTIVE_BOOKING_STATUSES = ['pending', 'confirmed'];

    private object $bookingsTable;
    private object $classesTable;

    public function __construct(?LocatorInterface $tableLocator = null)
    {
        $locator = $tableLocator ?? FactoryLocator::get('Table');
        $this->bookingsTable = $locator->get('Bookings');
        $this->classesTable = $locator->get('Classes');
    }

    public function createBookingForStudent(int $classId, int $studentId, ?int $parentId = null, array $options = []): array
    {
        $connection = $this->bookingsTable->getConnection();
        $hasExplicitAllowedStatuses = array_key_exists('allowedClassStatuses', $options);
        $requestedStatuses = array_values(array_unique(array_map(
            static fn(mixed $status): string => strtolower(trim((string)$status)),
            (array)($options['allowedClassStatuses'] ?? self::DEFAULT_ALLOWED_CLASS_STATUSES)
        )));
        $invalidStatuses = array_values(array_diff($requestedStatuses, self::BOOKABLE_CLASS_STATUSES));
        if ($hasExplicitAllowedStatuses && $invalidStatuses !== []) {
            throw new InvalidArgumentException(
                'Unsupported class statuses were provided: ' . implode(', ', $invalidStatuses)
            );
        }
        $allowedClassStatuses = array_values(array_intersect($requestedStatuses, self::BOOKABLE_CLASS_STATUSES));
        if ($allowedClassStatuses === []) {
            if ($hasExplicitAllowedStatuses) {
                throw new InvalidArgumentException('No valid class statuses were provided.');
            }

            $allowedClassStatuses = self::DEFAULT_ALLOWED_CLASS_STATUSES;
        }

        return $connection->transactional(function () use ($classId, $studentId, $parentId, $allowedClassStatuses): array {
            $classQuery = $this->classesTable->find()
                ->contain(['Courses', 'Teachers'])
                ->where(['Classes.class_id' => $classId]);

            if ($this->supportsRowLocking()) {
                $classQuery->epilog('FOR UPDATE');
            }

            $class = $classQuery->firstOrFail();
            if (!in_array((string)$class->class_status, $allowedClassStatuses, true)) {
                throw new RuntimeException('This class is not open for booking.');
            }

            $activeBooking = $this->bookingsTable->find()
                ->where([
                    'Bookings.student_id' => $studentId,
                    'Bookings.class_id' => $classId,
                    'Bookings.booking_status IN' => self::ACTIVE_BOOKING_STATUSES,
                ])
                ->orderBy([
                    'Bookings.updated_at' => 'DESC',
                    'Bookings.booking_id' => 'DESC',
                ])
                ->first();

            if ($activeBooking) {
                throw new RuntimeException('This student is already booked for this class.');
            }

            $activeCount = $this->bookingsTable->find()
                ->where([
                    'Bookings.class_id' => $classId,
                    'Bookings.booking_status IN' => self::ACTIVE_BOOKING_STATUSES,
                ])
                ->count();

            if ($activeCount >= (int)$class->capacity) {
                throw new RuntimeException('This class is fully booked.');
            }

            $cancelledBooking = $this->bookingsTable->find()
                ->where([
                    'Bookings.student_id' => $studentId,
                    'Bookings.class_id' => $classId,
                    'Bookings.booking_status' => 'cancelled',
                ])
                ->orderBy([
                    'Bookings.updated_at' => 'DESC',
                    'Bookings.booking_id' => 'DESC',
                ])
                ->first();

            if ($cancelledBooking) {
                $now = DateTime::now();
                $cancelledBooking->booking_status = 'pending';
                $cancelledBooking->parent_id = $parentId ?? $cancelledBooking->parent_id;
                $cancelledBooking->price_at_booking = $class->course?->course_price ?? 0;
                $cancelledBooking->booking_date = $now;
                $cancelledBooking->updated_at = $now;
                $this->bookingsTable->saveOrFail($cancelledBooking);

                return [
                    'booking' => $cancelledBooking,
                    'class' => $class,
                    'reactivated' => true,
                ];
            }

            $existingBooking = $this->bookingsTable->find()
                ->where([
                    'Bookings.student_id' => $studentId,
                    'Bookings.class_id' => $classId,
                ])
                ->orderBy([
                    'Bookings.updated_at' => 'DESC',
                    'Bookings.booking_id' => 'DESC',
                ])
                ->first();

            if ($existingBooking) {
                throw new RuntimeException('A booking record for this class already exists and cannot be duplicated.');
            }

            $now = DateTime::now();
            $booking = $this->bookingsTable->newEntity([
                'class_id' => $classId,
                'student_id' => $studentId,
                'parent_id' => $parentId,
                'booking_status' => 'pending',
                'price_at_booking' => $class->course?->course_price ?? 0,
                'booking_date' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $this->bookingsTable->saveOrFail($booking);

            return [
                'booking' => $booking,
                'class' => $class,
                'reactivated' => false,
            ];
        });
    }

    private function supportsRowLocking(): bool
    {
        return $this->bookingsTable->getConnection()->getDriver() instanceof Mysql;
    }
}
