<?php
declare(strict_types=1);

namespace App\Controller\Admin;

class TeachersController extends AppController
{
    public function index(): void
    {
        $teachersTable = $this->fetchTable('Teachers');
        $query = $teachersTable->find()
            ->contain(['Users'])
            ->order(['Teachers.teacher_name' => 'ASC']);

        $status = $this->request->getQuery('status');
        if ($status && in_array($status, ['active', 'inactive'])) {
            $query->where(['Teachers.teacher_status' => $status]);
        }

        $teachers = $this->paginate($query, ['limit' => 20]);

        $this->set(compact('teachers', 'status'));
    }

    public function view(?string $id = null): void
    {
        $teachersTable = $this->fetchTable('Teachers');
        $teacher = $teachersTable->get($id, contain: ['Users', 'Classes' => ['Courses']]);
        $this->set(compact('teacher'));
    }

    public function add()
    {
        $teachersTable = $this->fetchTable('Teachers');
        $usersTable = $this->fetchTable('Users');
        $teacher = $teachersTable->newEmptyEntity();

        if ($this->request->is('post')) {
            $data = $this->request->getData();

            $userData = [
                'username' => $data['username'],
                'email' => $data['email'],
                'password_hash' => $data['password'],
                'user_role' => 'teacher',
                'account_status' => 'active',
            ];

            $user = $usersTable->newEntity($userData);
            if ($usersTable->save($user)) {
                $teacher = $teachersTable->patchEntity($teacher, [
                    'user_id' => $user->user_id,
                    'teacher_name' => $data['teacher_name'],
                    'phone_number' => $data['phone_number'] ?? null,
                    'specialization' => $data['specialization'] ?? null,
                    'teacher_status' => $data['teacher_status'] ?? 'active',
                    'hire_date' => $data['hire_date'] ?? null,
                ]);

                if ($teachersTable->save($teacher)) {
                    $this->Flash->success(__('The teacher has been saved.'));

                    return $this->redirect(['action' => 'index']);
                }
            }
            $this->Flash->error(__('The teacher could not be saved. Please try again.'));
        }

        $this->set(compact('teacher'));
    }

    public function edit(?string $id = null)
    {
        $teachersTable = $this->fetchTable('Teachers');
        $teacher = $teachersTable->get($id, contain: ['Users']);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $teacher = $teachersTable->patchEntity($teacher, $this->request->getData());
            if ($teachersTable->save($teacher)) {
                $this->Flash->success(__('The teacher has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The teacher could not be saved. Please try again.'));
        }

        $this->set(compact('teacher'));
    }

    public function delete(?string $id = null)
    {
        $this->request->allowMethod(['post', 'delete']);

        $teachersTable = $this->fetchTable('Teachers');
        $teacher = $teachersTable->get($id);
        if ($teachersTable->delete($teacher)) {
            $this->Flash->success(__('The teacher has been deleted.'));
        } else {
            $this->Flash->error(__('The teacher could not be deleted. Please try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
