<?php
declare(strict_types=1);

namespace App\Controller\Admin;

class ClassesController extends AppController
{
    public function index(): void
    {
        $classesTable = $this->fetchTable('Classes');
        $query = $classesTable->find()
            ->contain(['Courses', 'Teachers'])
            ->order(['Classes.start_datetime' => 'DESC']);

        $status = $this->request->getQuery('status');
        if ($status && in_array($status, ['scheduled', 'ongoing', 'completed', 'cancelled', 'full'])) {
            $query->where(['Classes.class_status' => $status]);
        }

        $classes = $this->paginate($query, ['limit' => 20]);

        $this->set(compact('classes', 'status'));
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
