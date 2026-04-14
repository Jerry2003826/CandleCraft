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

        $search = $this->request->getQuery('search');
        if ($search) {
            $query->where(['Students.student_name LIKE' => '%' . $search . '%']);
        }

        $students = $this->paginate($query, ['limit' => 20]);

        $this->set(compact('students', 'status', 'search'));
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

    public function verifyAge(?string $id = null)
    {
        $this->request->allowMethod(['post']);

        $studentsTable = $this->fetchTable('Students');
        $usersTable = $this->fetchTable('Users');

        $student = $studentsTable->get($id, contain: ['Users']);

        if (!$student->user) {
            $this->Flash->error(__('This student has no linked user account.'));

            return $this->redirect(['action' => 'view', $id]);
        }

        $user = $usersTable->get($student->user->user_id);
        $user->age_verified_by_admin = true;

        if ($usersTable->save($user)) {
            $this->Flash->success(__('Age verified for {0}. Payment features are now enabled.', $student->student_name));
        } else {
            $this->Flash->error(__('Could not verify age. Please try again.'));
        }

        return $this->redirect(['action' => 'view', $id]);
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
