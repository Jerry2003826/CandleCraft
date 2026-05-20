<?php
declare(strict_types=1);

namespace App\Controller\Teacher;

use Cake\Http\Exception\ForbiddenException;

class ScheduleController extends AppController
{
    /**
     * Index.
     */
    public function index(): void
    {
        $identity = $this->Authentication->getIdentity();
        $teachersTable = $this->fetchTable('Teachers');
        $classesTable = $this->fetchTable('Classes');

        $teacher = $teachersTable->find()
            ->where(['Teachers.user_id' => $identity?->get('user_id')])
            ->firstOrFail();

        $classes = $classesTable->find()
            ->contain(['Courses', 'Bookings'])
            ->where(['Classes.teacher_id' => $teacher->teacher_id])
            ->orderBy(['Classes.start_datetime' => 'ASC'])
            ->all();

        $upcoming = [];
        $past = [];
        foreach ($classes as $class) {
            if ($class->start_datetime && $class->start_datetime->isFuture()) {
                $upcoming[] = $class;
            } else {
                $past[] = $class;
            }
        }
        $past = array_reverse($past);

        $this->set(compact('teacher', 'upcoming', 'past'));
        $this->set('title', 'My Schedule');
    }

    /**
     * View.
     *
     * @param mixed $classId Classid.
     */
    public function view(string $classId): void
    {
        $identity = $this->Authentication->getIdentity();
        $teachersTable = $this->fetchTable('Teachers');
        $classesTable = $this->fetchTable('Classes');

        $teacher = $teachersTable->find()
            ->where(['Teachers.user_id' => $identity?->get('user_id')])
            ->firstOrFail();

        $class = $classesTable->find()
            ->contain([
                'Courses',
                'Bookings' => [
                    'Students',
                    'AttendanceRecords',
                ],
            ])
            ->where(['Classes.class_id' => $classId])
            ->firstOrFail();

        if ((int)$class->teacher_id !== (int)$teacher->teacher_id) {
            throw new ForbiddenException('You do not have access to this class.');
        }

        $this->set(compact('class', 'teacher'));
        $this->set('title', $class->course?->course_name ?? $class->class_code);
    }
}
