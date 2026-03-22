<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController as BaseAppController;
use Cake\Event\EventInterface;

class AppController extends BaseAppController
{
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        $identity = $this->Authentication->getIdentity();
        if (!$identity || $identity->get('user_role') !== 'admin') {
            $this->Flash->error(__('You do not have permission to access the admin area.'));
            $this->Authentication->logout();
            $this->redirect(['prefix' => false, 'controller' => 'Users', 'action' => 'login']);

            return;
        }

        $this->viewBuilder()->setLayout('admin');
    }
}
