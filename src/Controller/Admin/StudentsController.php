<?php
declare(strict_types=1);

namespace App\Controller\Admin;

class StudentsController extends AppController
{
    public function index(): void
    {
        $studentsTable = $this->fetchTable('Students');
        $query = $studentsTable->find()
            ->contain(['Users'])
            ->order(['Students.student_name' => 'ASC']);

        $status = $this->request->getQuery('status');
        if ($status && in_array($status, ['active', 'inactive'])) {
            $query->where(['Students.student_status' => $status]);
        }

        $students = $this->paginate($query, ['limit' => 20]);

        $this->set(compact('students', 'status'));
    }

    public function view(?string $id = null): void
    {
        $studentsTable = $this->fetchTable('Students');
        $student = $studentsTable->get($id, contain: ['Users']);
        $this->set(compact('student'));
    }

    public function add()
    {
        $studentsTable = $this->fetchTable('Students');
        $student = $studentsTable->newEmptyEntity();

        if ($this->request->is('post')) {
            $student = $studentsTable->patchEntity($student, $this->request->getData());
            if ($studentsTable->save($student)) {
                $this->Flash->success(__('The student has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The student could not be saved. Please try again.'));
        }

        $this->set(compact('student'));
    }

    public function edit(?string $id = null)
    {
        $studentsTable = $this->fetchTable('Students');
        $student = $studentsTable->get($id);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $student = $studentsTable->patchEntity($student, $this->request->getData());
            if ($studentsTable->save($student)) {
                $this->Flash->success(__('The student has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The student could not be saved. Please try again.'));
        }

        $this->set(compact('student'));
    }

    public function delete(?string $id = null)
    {
        $this->request->allowMethod(['post', 'delete']);

        $studentsTable = $this->fetchTable('Students');
        $student = $studentsTable->get($id);
        if ($studentsTable->delete($student)) {
            $this->Flash->success(__('The student has been deleted.'));
        } else {
            $this->Flash->error(__('The student could not be deleted. Please try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
