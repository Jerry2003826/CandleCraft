<?php
declare(strict_types=1);

namespace App\Controller\Parent;

class DashboardController extends AppController
{
    private function loadParentChildrenData(): array
    {
        $identity = $this->Authentication->getIdentity();
        $parentsTable = $this->fetchTable('Parents');
        $parentStudentsTable = $this->fetchTable('ParentStudents');

        $parent = $parentsTable->find()
            ->where(['Parents.user_id' => $identity?->get('user_id')])
            ->firstOrFail();

        $parentLinks = $parentStudentsTable->find()
            ->where(['ParentStudents.parent_id' => $parent->parent_id])
            ->contain(['Students'])
            ->all();

        $studentIds = $parentLinks->extract('student_id')->toList();
        $children = $parentLinks->extract('student')->filter()->toList();

        return [$parent, $studentIds, $children];
    }

    public function index(): void
    {
        $bookingsTable = $this->fetchTable('Bookings');
        $attendanceTable = $this->fetchTable('AttendanceRecords');
        [$parent, $studentIds, $children] = $this->loadParentChildrenData();

        $bookings = [];
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
        }

        $bookingCount = is_iterable($bookings) ? count($bookings->toList()) : 0;
        $upcomingCount = 0;
        foreach ($bookings as $booking) {
            if ($booking->class_entity?->start_datetime && $booking->class_entity->start_datetime->isFuture()) {
                $upcomingCount++;
            }
        }

        $attendanceCount = 0;
        $presentCount = 0;
        if (!empty($studentIds)) {
            $attendanceRows = $attendanceTable->find()
                ->contain(['Bookings'])
                ->where(['Bookings.student_id IN' => $studentIds])
                ->all();
            foreach ($attendanceRows as $row) {
                $attendanceCount++;
                if ($row->attendance_status === 'present') {
                    $presentCount++;
                }
            }
        }

        $recentBookings = is_iterable($bookings) ? array_slice($bookings->toList(), 0, 5) : [];

        $this->set(compact(
            'parent',
            'children',
            'bookingCount',
            'upcomingCount',
            'attendanceCount',
            'presentCount',
            'recentBookings',
        ));
        $this->set('title', 'Parent Dashboard');
    }

    public function children(): void
    {
        [, , $children] = $this->loadParentChildrenData();

        $this->set(compact('children'));
        $this->set('title', 'My Children');
    }
}
