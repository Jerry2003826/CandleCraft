<?php
declare(strict_types=1);

namespace App\Controller\Parent;

class CoursesController extends AppController
{
    public function index(): void
    {
        $coursesTable = $this->fetchTable('Courses');
        $classesTable = $this->fetchTable('Classes');
        $bookingsTable = $this->fetchTable('Bookings');

        $courses = $coursesTable->find()->all();

        $courseData = [];
        foreach ($courses as $course) {
            $classes = $classesTable->find()
                ->where([
                    'Classes.course_id' => $course->course_id,
                    'Classes.class_status IN' => ['scheduled', 'ongoing'],
                ])
                ->contain(['Teachers'])
                ->order(['Classes.start_datetime' => 'ASC'])
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
            }

            $courseData[] = [
                'course' => $course,
                'classes' => $classList,
            ];
        }

        $this->set(compact('courseData'));
        $this->set('title', 'Browse Courses');
    }
}
