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
            'icon' => 'bi bi-person-workspace',
            'welcome' => 'Teaching Hub',
            'nav' => [
                [
                    'label' => 'View Schedule',
                    'icon' => 'bi bi-calendar-event',
                    'url' => ['prefix' => 'Teacher', 'controller' => 'Availability', 'action' => 'index'],
                    'controller' => 'Availability',
                    'action' => 'index',
                ],
                [
                    'label' => 'Manage Availability',
                    'icon' => 'bi bi-sliders',
                    'url' => ['prefix' => 'Teacher', 'controller' => 'Availability', 'action' => 'edit'],
                    'controller' => 'Availability',
                    'action' => 'edit',
                ],
                [
                    'label' => 'Manage Attendance',
                    'icon' => 'bi bi-clipboard-check',
                    'url' => ['prefix' => 'Teacher', 'controller' => 'Attendance', 'action' => 'index'],
                    'controller' => 'Attendance',
                ],
                [
                    'label' => 'Manage Learning Resources',
                    'icon' => 'bi bi-folder',
                    'url' => ['prefix' => 'Teacher', 'controller' => 'Resources', 'action' => 'index'],
                    'controller' => 'Resources',
                ],
            ],
        ]);
    }
}
