<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Teacher;

use App\Test\TestCase\Controller\AppIntegrationTestCase;

class AttendanceControllerTest extends AppIntegrationTestCase
{
    public function testIndexCannotViewAnotherTeachersClass(): void
    {
        $this->loginAsTeacher();

        $this->get('/teacher/attendance?class_id=2');

        $this->assertResponseCode(404);
    }

    public function testMarkCannotUpdateAnotherTeachersBooking(): void
    {
        $this->loginAsTeacher();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/teacher/attendance/mark', [
            'booking_id' => 2,
            'class_id' => 2,
            'attendance_status' => 'present',
            'attendance_notes' => 'Attempted overwrite',
        ]);

        $this->assertResponseCode(404);
    }

    public function testMarkRejectsInvalidAttendanceStatus(): void
    {
        $this->loginAsTeacher();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/teacher/attendance/mark', [
            'booking_id' => 1,
            'class_id' => 1,
            'attendance_status' => 'unknown',
            'attendance_notes' => '',
        ]);

        $this->assertResponseCode(400);
    }
}
