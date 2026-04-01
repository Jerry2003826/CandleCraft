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
            'icon' => '&#x1F46A;',
            'welcome' => 'Family Hub',
            'nav' => [
                [
                    'label' => 'Dashboard',
                    'icon' => '&#x1F4CA;',
                    'url' => ['prefix' => 'Parent', 'controller' => 'Dashboard', 'action' => 'index'],
                    'controller' => 'Dashboard',
                    'action' => 'index',
                ],
                [
                    'label' => 'My Children',
                    'icon' => '&#x1F476;',
                    'url' => ['prefix' => 'Parent', 'controller' => 'Dashboard', 'action' => 'children'],
                    'controller' => 'Dashboard',
                    'action' => 'children',
                ],
                [
                    'label' => 'Browse Courses',
                    'icon' => '&#x1F4DA;',
                    'url' => ['prefix' => 'Parent', 'controller' => 'Courses', 'action' => 'index'],
                    'controller' => 'Courses',
                ],
                [
                    'label' => 'Bookings',
                    'icon' => '&#x1F4C5;',
                    'url' => ['prefix' => 'Parent', 'controller' => 'Bookings', 'action' => 'index'],
                    'controller' => 'Bookings',
                ],
                [
                    'label' => 'Payments',
                    'icon' => '&#x1F4B3;',
                    'url' => ['prefix' => 'Parent', 'controller' => 'Payments', 'action' => 'index'],
                    'controller' => 'Payments',
                ],
            ],
        ]);
    }
}
