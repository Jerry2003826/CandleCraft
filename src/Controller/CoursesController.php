<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\EventInterface;
use Cake\Http\Response;

class CoursesController extends AppController
{
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);
        $this->Authentication->addUnauthenticatedActions(['index', 'view']);
    }

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

    public function view(?int $courseId = null): ?Response
    {
        $coursesTable = $this->fetchTable('Courses');
        $classesTable = $this->fetchTable('Classes');

        $course = $coursesTable->get($courseId);

        $classes = $classesTable->find()
            ->where([
                'Classes.course_id' => $courseId,
                'Classes.class_status IN' => ['scheduled', 'ongoing'],
            ])
            ->contain(['Teachers'])
            ->order(['Classes.start_datetime' => 'ASC'])
            ->all();

        foreach ($classes as $class) {
            $bookingsCount = $this->fetchTable('Bookings')->find()
                ->where([
                    'Bookings.class_id' => $class->class_id,
                    'Bookings.booking_status IN' => ['pending', 'confirmed'],
                ])
                ->count();
            $class->booked_count = $bookingsCount;
            $class->available_slots = $class->capacity - $bookingsCount;
        }

        $this->set(compact('course', 'classes'));
        $this->set('title', h($course->course_name));

        return null;
    }
}
