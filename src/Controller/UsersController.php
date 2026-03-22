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

        $result = $this->Authentication->getResult();

        if ($result && $result->isValid()) {
            $user = $this->Authentication->getIdentity();
            if ($user->get('user_role') === 'admin') {
                return $this->redirect(['prefix' => 'Admin', 'controller' => 'Dashboard', 'action' => 'index']);
            }

            return $this->redirect('/');
        }

        if ($this->request->is('post') && $result && !$result->isValid()) {
            $this->Flash->error(__('Invalid email or password.'));
        }

        return null;
    }

    public function logout(): ?Response
    {
        $this->Authentication->logout();
        $this->Flash->success(__('You have been logged out.'));

        return $this->redirect(['action' => 'login']);
    }
}
