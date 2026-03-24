<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use RuntimeException;

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
                'username' => $data['username'] ?? null,
                'email' => $data['email'] ?? null,
                'password_hash' => $data['password'] ?? null,
                'user_role' => 'teacher',
                'account_status' => 'active',
            ];

            $user = $usersTable->newEntity($userData);
            $teacher = $teachersTable->patchEntity($teacher, [
                'teacher_name' => $data['teacher_name'] ?? null,
                'phone_number' => $data['phone_number'] ?? null,
                'specialization' => $data['specialization'] ?? null,
                'teacher_status' => $data['teacher_status'] ?? 'active',
                'hire_date' => $data['hire_date'] ?? null,
            ]);

            if ($user->hasErrors()) {
                $this->Flash->error($this->extractFirstValidationError($user->getErrors(), 'The account information is invalid.'));

                return null;
            }

            if ($teacher->hasErrors()) {
                $this->Flash->error($this->extractFirstValidationError($teacher->getErrors(), 'The teacher profile information is invalid.'));

                return null;
            }

            // Keep user + teacher creation atomic to avoid partial records.
            $connection = $teachersTable->getConnection();
            $saved = false;
            $connection->begin();
            try {
                $savedUser = $usersTable->save($user);
                if (!$savedUser) {
                    throw new RuntimeException($this->extractFirstValidationError($user->getErrors(), 'Could not create teacher account.'));
                }

                $teacher->user_id = $savedUser->user_id;
                $savedTeacher = $teachersTable->save($teacher);
                if (!$savedTeacher) {
                    throw new RuntimeException($this->extractFirstValidationError($teacher->getErrors(), 'Could not save teacher profile.'));
                }

                $connection->commit();
                $saved = true;
            } catch (\Throwable $exception) {
                $connection->rollback();
                $this->Flash->error($exception->getMessage());
            }

            if ($saved) {
                $this->Flash->success(__('The teacher has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
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

    private function extractFirstValidationError(array $errors, string $fallback): string
    {
        foreach ($errors as $fieldErrors) {
            if (!is_array($fieldErrors)) {
                continue;
            }

            foreach ($fieldErrors as $message) {
                if (is_string($message) && $message !== '') {
                    return $message;
                }
            }
        }

        return $fallback;
    }
}
