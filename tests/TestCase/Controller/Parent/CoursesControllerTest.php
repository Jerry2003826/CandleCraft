<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Parent;

use App\Test\TestCase\Controller\AppIntegrationTestCase;

class CoursesControllerTest extends AppIntegrationTestCase
{
    public function testParentCoursesPageDoesNotExposeAttendanceOrAvailabilityDetails(): void
    {
        $this->loginAsParent();

        $this->get('/parent/courses');

        $this->assertResponseOk();
        $this->assertResponseNotContains('Payment Disputes');
        $this->assertResponseNotContains('Webhook Incidents');
        $this->assertResponseNotContains('Attendance');
        $this->assertResponseNotContains('Availability');
        $this->assertResponseNotContains('spots left');
        $this->assertResponseNotContains('Full');
    }

    public function testAlreadyBookedClassShowsBookedStatus(): void
    {
        $this->loginAsParent();

        $this->get('/parent/courses');

        $this->assertResponseOk();
        $this->assertResponseContains('已订购');
    }

    public function testParentBookingFormDoesNotExposeAvailabilityDetails(): void
    {
        $this->loginAsParent();

        $this->get('/parent/bookings/add/1/1');

        $this->assertResponseOk();
        $this->assertResponseNotContains('Availability');
        $this->assertResponseNotContains('spots');
    }
}
