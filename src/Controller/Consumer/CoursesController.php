<?php
declare(strict_types=1);

namespace App\Controller\Consumer;

use Cake\I18n\DateTime;

class CoursesController extends AppController
{
    public function index(): void
    {
        $coursesTable = $this->fetchTable('Courses');
        $classesTable = $this->fetchTable('Classes');
        $bookingsTable = $this->fetchTable('Bookings');

        $courses = $coursesTable->find()
            ->where(['Courses.is_active' => true])
            ->all();

        $calendarEvents = [];
        $courseData = [];
        foreach ($courses as $course) {
            $classes = $classesTable->find()
                ->where([
                    'Classes.course_id' => $course->course_id,
                    'Classes.class_status IN' => ['scheduled', 'ongoing'],
                ])
                ->contain(['Teachers'])
                ->orderBy(['Classes.start_datetime' => 'ASC'])
                ->all();

            $classList = [];
            foreach ($classes as $class) {
                $bookedCount = $bookingsTable->find()
                    ->where([
                        'Bookings.class_id' => $class->class_id,
                        'Bookings.booking_status IN' => ['pending', 'confirmed'],
                    ])
                    ->count();
                $class->booked_count = $bookedCount;
                $class->available_slots = $class->capacity - $bookedCount;
                $classList[] = $class;

                if ($class->start_datetime && $class->end_datetime) {
                    $courseType = strtolower($course->course_type ?? 'default');
                    $color = $courseType === 'pottery' ? '#1D4ED8' : ($courseType === 'knitting' ? '#B45309' : '#374151');
                    
                    $calendarEvents[] = [
                        'class_id' => $class->class_id,
                        'title' => $course->course_name . ' (' . $class->class_code . ')',
                        'start_hour' => (int)$class->start_datetime->format('G'),
                        'start_minute' => (int)$class->start_datetime->format('i'),
                        'end_hour' => (int)$class->end_datetime->format('G'),
                        'end_minute' => (int)$class->end_datetime->format('i'),
                        'day_index' => (int)$class->start_datetime->format('w'),
                        'full_date' => $class->start_datetime->format('Y-m-d'),
                        'color' => $color,
                        'location' => $class->location,
                        'teacher' => $class->teacher?->teacher_name,
                        'available_slots' => $class->available_slots,
                        'price' => $course->course_price
                    ];
                }
            }

            $courseData[] = [
                'course' => $course,
                'classes' => $classList,
            ];
        }

        // Handle week navigation for calendar
        $weekStart = $this->resolveWeekReference($this->request->getQuery('week_start'));
        if ($this->request->getQuery('week_start') === null) {
            $weekStart = $weekStart->modify('-' . date('w') . ' days');
        }
        $weekEnd = clone $weekStart;
        $weekEnd = $weekEnd->modify('+6 days');

        $this->set(compact('courseData', 'calendarEvents', 'weekStart', 'weekEnd'));
        $this->set('title', 'Book a Class');
    }

    private function resolveWeekReference(mixed $weekStartParam): DateTime
    {
        if (!is_string($weekStartParam) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $weekStartParam) !== 1) {
            return new DateTime('now');
        }

        try {
            return new DateTime($weekStartParam);
        } catch (\Throwable) {
            return new DateTime('now');
        }
    }
}
