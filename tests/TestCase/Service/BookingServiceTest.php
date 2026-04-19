<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\BookingService;
use Cake\Datasource\FactoryLocator;
use Cake\TestSuite\TestCase;
use RuntimeException;

class BookingServiceTest extends TestCase
{
    protected array $fixtures = [
        'app.Bookings',
        'app.Classes',
        'app.Courses',
        'app.Teachers',
        'app.Students',
        'app.Parents',
        'app.ParentStudents',
        'app.Users',
    ];

    public function testDuplicateBookingIsReportedBeforeCapacityReachedMessage(): void
    {
        $classes = FactoryLocator::get('Table')->get('Classes');
        $class = $classes->get(1);
        $class->capacity = 1;
        $classes->saveOrFail($class);

        $service = new BookingService();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('This student is already booked for this class.');

        $service->createBookingForStudent(1, 1);
    }

    public function testCancelledBookingCanBeReactivatedForParentThroughSharedService(): void
    {
        $bookings = FactoryLocator::get('Table')->get('Bookings');
        $booking = $bookings->get(1);
        $booking->booking_status = 'cancelled';
        $booking->parent_id = null;
        $bookings->saveOrFail($booking);

        $service = new BookingService();
        $result = $service->createBookingForStudent(1, 1, 1, [
            'allowedClassStatuses' => BookingService::PARENT_ALLOWED_CLASS_STATUSES,
        ]);

        $booking = $bookings->get(1);

        $this->assertTrue($result['reactivated']);
        $this->assertSame(1, $booking->booking_id);
        $this->assertSame('pending', $booking->booking_status);
        $this->assertSame(1, $booking->parent_id);
    }
}
