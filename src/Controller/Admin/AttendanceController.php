<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\Http\Response;

class AttendanceController extends AppController
{
    public function index(): void
    {
        $attendanceRecordsTable = $this->fetchTable('AttendanceRecords');
        $query = $attendanceRecordsTable->find()
            ->contain([
                'Bookings' => ['Students', 'Classes' => ['Courses']],
            ])
            ->orderBy(['AttendanceRecords.attendance_id' => 'DESC']);

        $statusFilter = $this->request->getQuery('status');
        if ($statusFilter) {
            $query->where(['AttendanceRecords.attendance_status' => $statusFilter]);
        }

        $records = $query->all();

        $stats = [
            'total' => $records->count(),
            'present' => $records->filter(fn($r) => $r->attendance_status === 'present')->count(),
            'absent' => $records->filter(fn($r) => $r->attendance_status === 'absent')->count(),
            'late' => $records->filter(fn($r) => $r->attendance_status === 'late')->count(),
            'excused' => $records->filter(fn($r) => $r->attendance_status === 'excused')->count(),
        ];

        $this->set(compact('records', 'stats', 'statusFilter'));
        $this->set('title', 'Attendance Records');
    }

    public function byClass(?int $classId = null): void
    {
        $classesTable = $this->fetchTable('Classes');
        $class = $classesTable->get($classId, ['contain' => ['Courses', 'Teachers']]);

        $bookingsTable = $this->fetchTable('Bookings');
        $bookings = $bookingsTable->find()
            ->where([
                'Bookings.class_id' => $classId,
                'Bookings.booking_status IN' => ['pending', 'confirmed', 'completed'],
            ])
            ->contain(['Students', 'AttendanceRecords'])
            ->all();

        $this->set(compact('class', 'bookings'));
        $this->set('title', 'Attendance - ' . h($class->class_code));
    }
}
