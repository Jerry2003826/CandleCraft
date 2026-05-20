<?php
declare(strict_types=1);

namespace App\Controller\Parent;

use App\Service\BookingEnrollmentStateService;

class CoursesController extends AppController
{
    /**
     * Index.
     */
    public function index(): void
    {
        $identity = $this->Authentication->getIdentity();
        $coursesTable = $this->fetchTable('Courses');
        $classesTable = $this->fetchTable('Classes');
        $enrollmentState = new BookingEnrollmentStateService();
        $parent = $this->fetchTable('Parents')->find()
            ->where(['Parents.user_id' => $identity->get('user_id')])
            ->firstOrFail();
        $studentIds = $this->fetchTable('ParentStudents')->find()
            ->where(['ParentStudents.parent_id' => $parent->parent_id])
            ->all()
            ->extract('student_id')
            ->toList();

        $courses = $coursesTable->find()->all();

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
            $courseHasCurrentCustomerBooking = false;
            foreach ($classes as $class) {
                $bookedCount = $enrollmentState->countBlockingBookingsForClass((int)$class->class_id);
                $class->booked_count = $bookedCount;
                $class->available_slots = $class->capacity - $bookedCount;
                $class->booked_by_current_customer = $enrollmentState->hasBlockingBookingForStudents(
                    (int)$class->class_id,
                    $studentIds,
                );
                $courseHasCurrentCustomerBooking = $courseHasCurrentCustomerBooking || (bool)$class->booked_by_current_customer;
                $classList[] = $class;
            }

            $courseData[] = [
                'course' => $course,
                'classes' => $classList,
                'booked_by_current_customer' => $courseHasCurrentCustomerBooking,
            ];
        }

        $this->set(compact('courseData'));
        $this->set('title', 'Browse Courses');
    }
}
