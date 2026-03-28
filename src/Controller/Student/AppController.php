<?php
declare(strict_types=1);

namespace App\Controller\Student;

use App\Controller\AppController as BaseAppController;
use Cake\Event\EventInterface;

class AppController extends BaseAppController
{
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        $identity = $this->Authentication->getIdentity();
        if (!$identity || $identity->get('user_role') !== 'student') {
            $this->Flash->error(__('Please sign in with a student account to continue.'));
            $this->Authentication->logout();
            $event->stopPropagation();
            $this->setResponse($this->redirect(['prefix' => false, 'controller' => 'Users', 'action' => 'login']));

            return;
        }

        $this->viewBuilder()->setLayout('portal');
        $this->set('portalContext', [
            'title' => 'Student Portal',
            'icon' => '&#x1F393;',
            'welcome' => 'Learning Hub',
            'nav' => [
                [
                    'label' => 'Dashboard',
                    'icon' => '&#x1F4DA;',
                    'url' => ['prefix' => 'Student', 'controller' => 'Dashboard', 'action' => 'index'],
                    'controller' => 'Dashboard',
                ],
                [
                    'label' => 'Browse Courses',
                    'icon' => '&#x1F3A8;',
                    'url' => ['prefix' => false, 'controller' => 'Courses', 'action' => 'index'],
                    'controller' => 'Courses',
                ],
                [
                    'label' => 'My Bookings',
                    'icon' => '&#x1F4C5;',
                    'url' => ['prefix' => 'Student', 'controller' => 'Bookings', 'action' => 'index'],
                    'controller' => 'Bookings',
                ],
                [
                    'label' => 'Learning Center',
                    'icon' => '&#x1F4C1;',
                    'url' => ['prefix' => 'Student', 'controller' => 'Resources', 'action' => 'index'],
                    'controller' => 'Resources',
                ],
                [
                    'label' => 'Notifications',
                    'icon' => '&#x1F514;',
                    'url' => ['prefix' => 'Student', 'controller' => 'Notifications', 'action' => 'index'],
                    'controller' => 'Notifications',
                ],
            ],
        ]);
    }
}
