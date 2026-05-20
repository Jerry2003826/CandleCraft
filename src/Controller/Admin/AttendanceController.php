<?php
declare(strict_types=1);

namespace App\Controller\Admin;

class AttendanceController extends AppController
{
    /**
     * Index.
     */
    public function index(): void
    {
        $classesTable = $this->fetchTable('Classes');
        $statusFilter = (string)$this->request->getQuery('status', '');
        $searchTerm = trim((string)$this->request->getQuery('q', ''));
        $validStatusFilters = ['present', 'absent', 'late', 'excused'];
        if (!in_array($statusFilter, $validStatusFilters, true)) {
            $statusFilter = '';
        }

        $query = $classesTable->find()
            ->contain([
                'Courses',
                'Teachers',
                'Bookings' => function ($q) {
                    return $q
                        ->where(['Bookings.booking_status IN' => ['pending', 'confirmed', 'completed']])
                        ->contain(['Students', 'AttendanceRecords']);
                },
            ])
            ->orderBy([
                'Classes.start_datetime' => 'DESC',
                'Classes.class_code' => 'ASC',
            ]);

        if ($statusFilter) {
            $query
                ->matching('Bookings.AttendanceRecords', function ($q) use ($statusFilter) {
                    return $q->where(['AttendanceRecords.attendance_status' => $statusFilter]);
                })
                ->distinct(['Classes.class_id']);
        }

        if ($searchTerm !== '') {
            $like = '%' . $searchTerm . '%';
            $query
                ->leftJoinWith('Courses')
                ->leftJoinWith('Teachers')
                ->where([
                    'OR' => [
                        'Classes.class_code LIKE' => $like,
                        'Classes.location LIKE' => $like,
                        'Courses.course_name LIKE' => $like,
                        'Teachers.teacher_name LIKE' => $like,
                    ],
                ])
                ->distinct(['Classes.class_id']);
        }

        $classes = $query->all();

        $stats = [
            'totalClasses' => $classes->count(),
            'totalRecords' => 0,
            'present' => 0,
            'absent' => 0,
            'late' => 0,
            'excused' => 0,
        ];

        foreach ($classes as $class) {
            $summary = [
                'bookings' => 0,
                'marked' => 0,
                'unmarked' => 0,
                'present' => 0,
                'absent' => 0,
                'late' => 0,
                'excused' => 0,
            ];

            foreach ($class->bookings ?? [] as $booking) {
                $summary['bookings']++;
                $attendance = $booking->attendance_record ?? null;
                if (!$attendance) {
                    continue;
                }

                $status = (string)$attendance->attendance_status;
                $summary['marked']++;
                if (array_key_exists($status, $summary)) {
                    $summary[$status]++;
                }
                if (array_key_exists($status, $stats)) {
                    $stats[$status]++;
                }
                $stats['totalRecords']++;
            }

            $summary['unmarked'] = max(0, $summary['bookings'] - $summary['marked']);
            $class->set('attendance_summary', $summary);
        }

        $this->set(compact('classes', 'stats', 'statusFilter', 'searchTerm'));
        $this->set('title', 'Attendance Records');
    }

    /**
     * By class.
     *
     * @param mixed $classId Classid.
     */
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
