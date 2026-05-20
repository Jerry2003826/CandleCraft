<?php
declare(strict_types=1);

namespace App\Controller\Consumer;

class DashboardController extends AppController
{
    /**
     * Index.
     */
    public function index(): void
    {
        $identity = $this->Authentication->getIdentity();
        $this->studentDashboard($identity);

        $this->set('title', 'Customer Portal');
    }

    /**
     * Student dashboard.
     *
     * @param mixed $identity Identity.
     */
    private function studentDashboard(mixed $identity): void
    {
        $studentsTable = $this->fetchTable('Students');
        $bookingsTable = $this->fetchTable('Bookings');

        $student = $studentsTable->find()
            ->contain(['Users'])
            ->where(['Students.user_id' => $identity->get('user_id')])
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
        $upcomingCount = $bookings->filter(
            fn($booking) => $booking->class_entity?->start_datetime && $booking->class_entity->start_datetime->isFuture(),
        )->count();
        $presentCount = $bookings->filter(
            fn($booking) => $booking->attendance_record?->attendance_status === 'present',
        )->count();

        $recentBookings = array_slice($bookings->toList(), 0, 5);

        $this->set(compact('student', 'bookings', 'bookingCount', 'upcomingCount', 'presentCount', 'recentBookings'));
        $this->set('children', []);
    }
}
