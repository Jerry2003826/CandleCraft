<?php
declare(strict_types=1);

namespace App\Controller\Student;

class DashboardController extends AppController
{
    public function index(): void
    {
        $identity = $this->Authentication->getIdentity();
        $studentsTable = $this->fetchTable('Students');
        $bookingsTable = $this->fetchTable('Bookings');

        $student = $studentsTable->find()
            ->contain(['Users'])
            ->where(['Students.user_id' => $identity?->get('user_id')])
            ->firstOrFail();

        $bookings = $bookingsTable->find()
            ->where(['Bookings.student_id' => $student->student_id])
            ->contain([
                'Classes' => ['Courses', 'Teachers'],
                'AttendanceRecords',
            ])
            ->orderBy(['Classes.start_datetime' => 'ASC'])
            ->all();

        $bookingCount = $bookings->count();
        $upcomingClasses = $bookings->filter(
            fn ($booking) => $booking->class?->start_datetime && $booking->class->start_datetime->isFuture(),
        )->count();
        $presentCount = $bookings->filter(
            fn ($booking) => $booking->attendance_record?->attendance_status === 'present',
        )->count();

        $this->set(compact('student', 'bookings', 'bookingCount', 'upcomingClasses', 'presentCount'));
        $this->set('title', 'Student Dashboard');
    }
}
