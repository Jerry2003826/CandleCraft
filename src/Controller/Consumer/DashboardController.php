<?php
declare(strict_types=1);

namespace App\Controller\Consumer;

class DashboardController extends AppController
{
    public function index(): void
    {
        $identity = $this->Authentication->getIdentity();

        if ($this->userRole === 'parent') {
            $this->parentDashboard($identity);
        } else {
            $this->studentDashboard($identity);
        }

        $this->set('title', 'Dashboard');
    }

    private function studentDashboard($identity): void
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
            ->order(['Classes.start_datetime' => 'ASC'])
            ->all();

        $bookingCount = $bookings->count();
        $upcomingCount = $bookings->filter(
            fn ($booking) => $booking->class_entity?->start_datetime && $booking->class_entity->start_datetime->isFuture(),
        )->count();
        $presentCount = $bookings->filter(
            fn ($booking) => $booking->attendance_record?->attendance_status === 'present',
        )->count();

        $recentBookings = array_slice($bookings->toList(), 0, 5);

        $this->set(compact('student', 'bookings', 'bookingCount', 'upcomingCount', 'presentCount', 'recentBookings'));
        $this->set('children', []);
    }

    private function parentDashboard($identity): void
    {
        $parentsTable = $this->fetchTable('Parents');
        $parentStudentsTable = $this->fetchTable('ParentStudents');
        $bookingsTable = $this->fetchTable('Bookings');
        $attendanceTable = $this->fetchTable('AttendanceRecords');

        $parent = $parentsTable->find()
            ->where(['Parents.user_id' => $identity->get('user_id')])
            ->firstOrFail();

        $parentLinks = $parentStudentsTable->find()
            ->where(['ParentStudents.parent_id' => $parent->parent_id])
            ->contain(['Students'])
            ->all();

        $studentIds = $parentLinks->extract('student_id')->toList();
        $children = $parentLinks->extract('student')->filter()->toList();

        $bookings = new \Cake\Collection\Collection([]);
        $bookingCount = 0;
        $upcomingCount = 0;
        $presentCount = 0;

        if (!empty($studentIds)) {
            $bookings = $bookingsTable->find()
                ->where(['Bookings.student_id IN' => $studentIds])
                ->contain([
                    'Students',
                    'Classes' => ['Courses', 'Teachers'],
                    'Payments',
                    'AttendanceRecords',
                ])
                ->order(['Bookings.booking_date' => 'DESC'])
                ->all();

            $bookingCount = count($bookings->toList());
            foreach ($bookings as $booking) {
                if ($booking->class_entity?->start_datetime && $booking->class_entity->start_datetime->isFuture()) {
                    $upcomingCount++;
                }
            }

            $attendanceRows = $attendanceTable->find()
                ->contain(['Bookings'])
                ->where(['Bookings.student_id IN' => $studentIds])
                ->all();
            foreach ($attendanceRows as $row) {
                if ($row->attendance_status === 'present') {
                    $presentCount++;
                }
            }
        }

        $recentBookings = array_slice($bookings->toList(), 0, 5);

        $this->set(compact('parent', 'children', 'bookingCount', 'upcomingCount', 'presentCount', 'recentBookings'));
        $this->set('student', null);
        $this->set('bookings', $bookings);
    }
}
