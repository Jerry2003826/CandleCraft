<?php
declare(strict_types=1);

namespace App\Controller\Teacher;

use App\Controller\AppController as BaseAppController;
use Cake\Event\EventInterface;

class AppController extends BaseAppController
{
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        $identity = $this->Authentication->getIdentity();
        if (!$identity || $identity->get('user_role') !== 'teacher') {
            $this->Flash->error(__('Please sign in with a teacher account to continue.'));
            $this->Authentication->logout();
            $event->stopPropagation();
            $this->setResponse($this->redirect(['prefix' => false, 'controller' => 'Users', 'action' => 'login']));

            return;
        }

        $this->viewBuilder()->setLayout('portal');
        $this->set('portalContext', [
            'title' => 'Teacher Portal',
            'icon' => '&#x1F468;&#x200D;&#x1F3EB;',
            'welcome' => 'Teaching Hub',
            'nav' => [
                [
                    'label' => 'Dashboard',
                    'icon' => '&#x1F4C5;',
                    'url' => ['prefix' => 'Teacher', 'controller' => 'Dashboard', 'action' => 'index'],
                    'controller' => 'Dashboard',
                ],
                [
                    'label' => 'Attendance',
                    'icon' => '&#x1F4CB;',
                    'url' => ['prefix' => 'Teacher', 'controller' => 'Attendance', 'action' => 'index'],
                    'controller' => 'Attendance',
                ],
                [
                    'label' => 'My Schedule',
                    'icon' => '&#x1F4C1;',
                    'url' => ['prefix' => 'Teacher', 'controller' => 'Availability', 'action' => 'index'],
                    'controller' => 'Availability',
                ],
            ],
        ]);
    }
}
