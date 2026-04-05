<?php
declare(strict_types=1);

namespace App\Controller\Parent;

use App\Controller\AppController as BaseAppController;
use Cake\Event\EventInterface;

class AppController extends BaseAppController
{
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        $identity = $this->Authentication->getIdentity();
        if (!$identity || $identity->get('user_role') !== 'parent') {
            $this->Flash->error(__('Please sign in with a parent account to continue.'));
            $this->Authentication->logout();
            $event->stopPropagation();
            $this->setResponse($this->redirect(['prefix' => false, 'controller' => 'Users', 'action' => 'login']));

            return;
        }

        $this->viewBuilder()->setLayout('portal');
        $this->set('portalContext', [
            'title' => 'Parent Portal',
            'icon' => 'bi bi-people',
            'welcome' => 'Family Hub',
            'nav' => [
                [
                    'label' => 'Dashboard',
                    'icon' => 'bi bi-house',
                    'url' => ['prefix' => 'Parent', 'controller' => 'Dashboard', 'action' => 'index'],
                    'controller' => 'Dashboard',
                    'action' => 'index',
                ],
                [
                    'label' => 'My Children',
                    'icon' => 'bi bi-person-hearts',
                    'url' => ['prefix' => 'Parent', 'controller' => 'Dashboard', 'action' => 'children'],
                    'controller' => 'Dashboard',
                    'action' => 'children',
                ],
                [
                    'label' => 'Browse Courses',
                    'icon' => 'bi bi-palette',
                    'url' => ['prefix' => 'Parent', 'controller' => 'Courses', 'action' => 'index'],
                    'controller' => 'Courses',
                ],
                [
                    'label' => 'My Schedule',
                    'icon' => 'bi bi-calendar-event',
                    'url' => ['prefix' => 'Parent', 'controller' => 'Bookings', 'action' => 'index'],
                    'controller' => 'Bookings',
                ],
                [
                    'label' => 'Payments',
                    'icon' => 'bi bi-credit-card',
                    'url' => ['prefix' => 'Parent', 'controller' => 'Payments', 'action' => 'index'],
                    'controller' => 'Payments',
                ],
                [
                    'label' => 'Learning Center',
                    'icon' => 'bi bi-folder',
                    'url' => ['prefix' => 'Parent', 'controller' => 'Resources', 'action' => 'index'],
                    'controller' => 'Resources',
                ],
                [
                    'label' => 'Notifications',
                    'icon' => 'bi bi-bell',
                    'url' => ['prefix' => 'Parent', 'controller' => 'Notifications', 'action' => 'index'],
                    'controller' => 'Notifications',
                ],
            ],
        ]);
    }
}
