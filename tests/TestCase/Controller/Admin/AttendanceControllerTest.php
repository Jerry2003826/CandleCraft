<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Controller\AppIntegrationTestCase;
use Cake\Datasource\FactoryLocator;

class AttendanceControllerTest extends AppIntegrationTestCase
{
    public function testIndexListsAttendanceByClassWithSearchButton(): void
    {
        $this->createAttendanceRecord(1, 'late');
        $this->loginAsAdmin();

        $this->get('/admin/attendance');

        $this->assertResponseOk();
        $this->assertResponseContains('Attendance by Class');
        $this->assertResponseContains('Search');
        $this->assertResponseContains('<th>Class</th>');
        $this->assertResponseContains('POT-101');
        $this->assertResponseContains('KNI-201');
        $this->assertResponseContains('1 / 1 marked');
        $this->assertResponseNotContains('<th>Student</th>');
    }

    public function testIndexSearchFiltersClasses(): void
    {
        $this->loginAsAdmin();

        $this->get('/admin/attendance?q=KNI');

        $this->assertResponseOk();
        $this->assertResponseContains('KNI-201');
        $this->assertResponseNotContains('POT-101');
    }

    public function testIndexStatusFilterKeepsClassLevelRows(): void
    {
        $this->createAttendanceRecord(1, 'late');
        $this->loginAsAdmin();

        $this->get('/admin/attendance?status=late');

        $this->assertResponseOk();
        $this->assertResponseContains('POT-101');
        $this->assertResponseContains('Late 1');
        $this->assertResponseNotContains('KNI-201');
        $this->assertResponseNotContains('<th>Student</th>');
    }

    private function createAttendanceRecord(int $bookingId, string $status): void
    {
        $records = FactoryLocator::get('Table')->get('AttendanceRecords');
        $record = $records->newEntity([
            'booking_id' => $bookingId,
            'marked_by_teacher_id' => 1,
            'attendance_status' => $status,
            'attendance_notes' => 'Recorded during admin attendance test.',
            'attendance_date' => '2026-05-18 09:00:00',
            'created_at' => '2026-05-18 09:00:00',
            'updated_at' => '2026-05-18 09:00:00',
        ]);

        $records->saveOrFail($record);
    }
}
