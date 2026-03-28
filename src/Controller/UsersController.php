<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Response;

class UsersController extends AppController
{
    public function beforeFilter(\Cake\Event\EventInterface $event): void
    {
        parent::beforeFilter($event);
        $this->Authentication->addUnauthenticatedActions(['login']);
    }

    public function login(): ?Response
    {
        $this->viewBuilder()->setLayout('login');
        // Prevent browsers/proxies from serving stale auth pages.
        $this->response = $this->response
            ->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->withHeader('Pragma', 'no-cache')
            ->withHeader('Expires', '0');

        $result = $this->Authentication->getResult();

        if ($result && $result->isValid()) {
            $user = $this->Authentication->getIdentity();
            $role = $user->get('user_role');

            if ($role === 'admin') {
                return $this->redirect(['prefix' => 'Admin', 'controller' => 'Dashboard', 'action' => 'index']);
            }

            if ($role === 'teacher') {
                return $this->redirect(['prefix' => 'Teacher', 'controller' => 'Dashboard', 'action' => 'index']);
            }

            if ($role === 'student') {
                return $this->redirect(['prefix' => 'Student', 'controller' => 'Dashboard', 'action' => 'index']);
            }

            if ($role === 'parent') {
                return $this->redirect(['prefix' => 'Parent', 'controller' => 'Dashboard', 'action' => 'index']);
            }

            $this->Flash->info(__('This portal currently supports admin, teacher, student, and parent logins.'));

            return $this->redirect('/');
        }

        if ($this->request->is('post') && $result && !$result->isValid()) {
            $this->Flash->error(__('Invalid email or password.'));
        }

        return null;
    }

    public function logout(): ?Response
    {
        $this->request->allowMethod(['post']);
        $this->Authentication->logout();
        $this->Flash->success(__('You have been logged out.'));

        return $this->redirect(['action' => 'login']);
    }
}
