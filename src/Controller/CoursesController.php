<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\BookingEnrollmentStateService;
use Cake\Event\EventInterface;
use Cake\Http\Response;

class CoursesController extends AppController
{
    /**
     * Before filter.
     *
     * @param mixed $event Event.
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);
        $this->Authentication->addUnauthenticatedActions(['index', 'view']);
    }

    /**
     * Index.
     */
    public function index(): void
    {
        $coursesTable = $this->fetchTable('Courses');
        $type = $this->request->getQuery('type');

        $query = $coursesTable->find()
            ->contain(['Classes' => function ($q) {
                return $q->where(['Classes.class_status IN' => ['scheduled', 'ongoing']]);
            }]);

        if ($type) {
            $query->where(['Courses.course_type' => strtolower($type)]);
        }

        $courses = $query->all();

        $this->set(compact('courses', 'type'));
        $this->set('title', $type ? ucfirst($type) . ' Courses' : 'Our Courses');
    }

    /**
     * View.
     *
     * @param mixed $courseId Courseid.
     */
    public function view(?int $courseId = null): ?Response
    {
        $coursesTable = $this->fetchTable('Courses');
        $classesTable = $this->fetchTable('Classes');
        $enrollmentState = new BookingEnrollmentStateService();

        $course = $coursesTable->get($courseId);

        $classes = $classesTable->find()
            ->where([
                'Classes.course_id' => $courseId,
                'Classes.class_status IN' => ['scheduled', 'ongoing'],
            ])
            ->contain(['Teachers'])
            ->orderBy(['Classes.start_datetime' => 'ASC'])
            ->all();

        foreach ($classes as $class) {
            $bookingsCount = $enrollmentState->countBlockingBookingsForClass((int)$class->class_id);
            $class->booked_count = $bookingsCount;
            $class->available_slots = $class->capacity - $bookingsCount;
        }

        $this->set(compact('course', 'classes'));
        $this->set('title', h($course->course_name));

        return null;
    }
}
