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
        $allowedClassStatuses = array_values(array_intersect(
            array_map('strval', (array)($options['allowedClassStatuses'] ?? self::DEFAULT_ALLOWED_CLASS_STATUSES)),
            self::BOOKABLE_CLASS_STATUSES
        ));
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

            $existingBooking = $this->bookingsTable->find()
                ->where([
                    'Bookings.student_id' => $studentId,
                    'Bookings.class_id' => $classId,
                ])
                ->first();

            if ($existingBooking && in_array($existingBooking->booking_status, ['pending', 'confirmed'], true)) {
                throw new RuntimeException('This student is already booked for this class.');
            }

            $activeCount = $this->bookingsTable->find()
                ->where([
                    'Bookings.class_id' => $classId,
                    'Bookings.booking_status IN' => ['pending', 'confirmed'],
                ])
                ->count();

            if ($activeCount >= (int)$class->capacity) {
                throw new RuntimeException('This class is fully booked.');
            }

            if ($existingBooking && $existingBooking->booking_status === 'cancelled') {
                $now = DateTime::now();
                $existingBooking->booking_status = 'pending';
                $existingBooking->parent_id = $parentId ?? $existingBooking->parent_id;
                $existingBooking->price_at_booking = $class->course?->course_price ?? 0;
                $existingBooking->booking_date = $now;
                $existingBooking->updated_at = $now;
                $this->bookingsTable->saveOrFail($existingBooking);

                return [
                    'booking' => $existingBooking,
                    'class' => $class,
                    'reactivated' => true,
                ];
            }

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
