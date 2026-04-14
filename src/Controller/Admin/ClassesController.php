<?php
declare(strict_types=1);

namespace App\Controller\Admin;

class ClassesController extends AppController
{
    public function index(): void
    {
        $classesTable = $this->fetchTable('Classes');
        $query = $classesTable->find()
            ->contain(['Courses', 'Teachers', 'Bookings'])
            ->order(['Classes.start_datetime' => 'DESC']);

        $search = $this->request->getQuery('search');
        if ($search) {
            $query->where([
                'OR' => [
                    'Classes.class_code LIKE' => "%{$search}%",
                    'Courses.course_name LIKE' => "%{$search}%",
                    'Teachers.teacher_name LIKE' => "%{$search}%",
                    'Classes.location LIKE' => "%{$search}%",
                ],
            ]);
        }

        $status = $this->request->getQuery('status');
        if ($status && in_array($status, ['scheduled', 'ongoing', 'completed', 'cancelled', 'full'])) {
            $query->where(['Classes.class_status' => $status]);
        }

        $classes = $this->paginate($query, ['limit' => 20]);

        $this->set(compact('classes', 'status', 'search'));
    }

    public function view(?string $id = null): void
    {
        $classesTable = $this->fetchTable('Classes');
        $class = $classesTable->get($id, contain: ['Courses', 'Teachers', 'Bookings' => ['Students']]);
        $this->set('class', $class);
    }

    public function add()
    {
        $classesTable = $this->fetchTable('Classes');
        $class = $classesTable->newEmptyEntity();

        if ($this->request->is('post')) {
            $class = $classesTable->patchEntity($class, $this->request->getData());
            if ($classesTable->save($class)) {
                $this->Flash->success(__('The class has been saved.'));

                $referer = $this->request->referer(true);
                if ($referer && str_contains($referer, 'availability')) {
                    return $this->redirect(['action' => 'availability']);
                }

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The class could not be saved. Please try again.'));
        }

        $courses = $classesTable->Courses->find('list', keyField: 'course_id', valueField: 'course_name')
            ->where(['is_active' => true])
            ->order(['course_name' => 'ASC'])
            ->all();

        $teachers = $classesTable->Teachers->find('list', keyField: 'teacher_id', valueField: 'teacher_name')
            ->where(['teacher_status' => 'active'])
            ->order(['teacher_name' => 'ASC'])
            ->all();

        $this->set(compact('class', 'courses', 'teachers'));
    }

    public function edit(?string $id = null)
    {
        $classesTable = $this->fetchTable('Classes');
        $class = $classesTable->get($id);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $class = $classesTable->patchEntity($class, $this->request->getData());
            if ($classesTable->save($class)) {
                $this->Flash->success(__('The class has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The class could not be saved. Please try again.'));
        }

        $courses = $classesTable->Courses->find('list', keyField: 'course_id', valueField: 'course_name')
            ->where(['is_active' => true])
            ->order(['course_name' => 'ASC'])
            ->all();

        $teachers = $classesTable->Teachers->find('list', keyField: 'teacher_id', valueField: 'teacher_name')
            ->where(['teacher_status' => 'active'])
            ->order(['teacher_name' => 'ASC'])
            ->all();

        $this->set(compact('class', 'courses', 'teachers'));
    }

    public function availability()
    {
        $classesTable = $this->fetchTable('Classes');

        $weekOffset = (int)($this->request->getQuery('week') ?? 0);
        $monday = new \DateTimeImmutable('monday this week');
        $monday = $monday->modify("{$weekOffset} weeks");

        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $days[] = $monday->modify("+{$i} days");
        }

        $startDate = $monday->format('Y-m-d 00:00:00');
        $endDate = $days[6]->format('Y-m-d 23:59:59');

        $classes = $classesTable->find()
            ->contain(['Courses', 'Teachers', 'Bookings'])
            ->where([
                'Classes.start_datetime >=' => $startDate,
                'Classes.start_datetime <=' => $endDate,
            ])
            ->order(['Classes.start_datetime' => 'ASC'])
            ->all();

        $classesByDay = [];
        foreach ($days as $day) {
            $classesByDay[$day->format('Y-m-d')] = [];
        }
        foreach ($classes as $class) {
            $dayKey = $class->start_datetime->format('Y-m-d');
            if (isset($classesByDay[$dayKey])) {
                $classesByDay[$dayKey][] = $class;
            }
        }

        $courses = $classesTable->Courses->find('list', keyField: 'course_id', valueField: 'course_name')
            ->where(['is_active' => true])
            ->order(['course_name' => 'ASC'])
            ->all();

        $teachers = $classesTable->Teachers->find('list', keyField: 'teacher_id', valueField: 'teacher_name')
            ->where(['teacher_status' => 'active'])
            ->order(['teacher_name' => 'ASC'])
            ->all();

        $allCourses = $classesTable->Courses->find()
            ->where(['is_active' => true])
            ->order(['course_name' => 'ASC'])
            ->all();

        $scheduledCourseIds = [];
        foreach ($classes as $class) {
            $scheduledCourseIds[$class->course_id] = true;
        }

        $this->set(compact('classesByDay', 'days', 'courses', 'teachers', 'weekOffset', 'allCourses', 'scheduledCourseIds'));
    }

    public function delete(?string $id = null)
    {
        $this->request->allowMethod(['post', 'delete']);

        $classesTable = $this->fetchTable('Classes');
        $class = $classesTable->get($id);
        if ($classesTable->delete($class)) {
            $this->Flash->success(__('The class has been deleted.'));
        } else {
            $this->Flash->error(__('The class could not be deleted. Please try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
