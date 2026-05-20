<?php
declare(strict_types=1);

namespace App\Controller\Student;

use App\Service\BookingEnrollmentStateService;

class CoursesController extends AppController
{
    /**
     * Index.
     */
    public function index(): void
    {
        $coursesTable = $this->fetchTable('Courses');
        $classesTable = $this->fetchTable('Classes');
        $enrollmentState = new BookingEnrollmentStateService();

        $courses = $coursesTable->find()
            ->where(['Courses.is_active' => true])
            ->all();

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
                $bookedCount = $enrollmentState->countBlockingBookingsForClass((int)$class->class_id);
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
