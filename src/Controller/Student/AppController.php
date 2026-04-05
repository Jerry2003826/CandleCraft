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
            'icon' => 'bi bi-mortarboard',
            'welcome' => 'Learning Hub',
            'nav' => [
                [
                    'label' => 'Dashboard',
                    'icon' => 'bi bi-house',
                    'url' => ['prefix' => 'Student', 'controller' => 'Dashboard', 'action' => 'index'],
                    'controller' => 'Dashboard',
                ],
                [
                    'label' => 'Browse Courses',
                    'icon' => 'bi bi-palette',
                    'url' => ['prefix' => 'Student', 'controller' => 'Courses', 'action' => 'index'],
                    'controller' => 'Courses',
                ],
                [
                    'label' => 'My Schedule',
                    'icon' => 'bi bi-calendar-event',
                    'url' => ['prefix' => 'Student', 'controller' => 'Bookings', 'action' => 'index'],
                    'controller' => 'Bookings',
                ],
                [
                    'label' => 'Payments',
                    'icon' => 'bi bi-credit-card',
                    'url' => ['prefix' => 'Student', 'controller' => 'Payments', 'action' => 'index'],
                    'controller' => 'Payments',
                ],
                [
                    'label' => 'Learning Center',
                    'icon' => 'bi bi-folder',
                    'url' => ['prefix' => 'Student', 'controller' => 'Resources', 'action' => 'index'],
                    'controller' => 'Resources',
                ],
                [
                    'label' => 'Notifications',
                    'icon' => 'bi bi-bell',
                    'url' => ['prefix' => 'Student', 'controller' => 'Notifications', 'action' => 'index'],
                    'controller' => 'Notifications',
                ],
            ],
        ]);
    }
}
