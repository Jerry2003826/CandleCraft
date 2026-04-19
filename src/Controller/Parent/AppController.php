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
        if (!$identity) {
            $this->shortCircuitRequest(
                $event,
                $this->rejectUnauthenticatedAccess('Please sign in with a parent account to continue.')
            );

            return;
        }

        if ($identity->get('user_role') !== 'parent') {
            $this->shortCircuitRequest(
                $event,
                $this->redirectAuthenticatedRoleMismatch($identity, 'Please sign in with a parent account to continue.')
            );

            return;
        }

        $parentExists = $this->fetchTable('Parents')->exists([
            'Parents.user_id' => $identity->get('user_id'),
        ]);
        if (!$parentExists) {
            $this->Flash->error(__('Your parent profile could not be found. Please contact an administrator.'));
            $this->Authentication->logout();
            $this->shortCircuitRequest(
                $event,
                $this->redirect(['prefix' => false, 'controller' => 'Users', 'action' => 'login'])
            );

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
                    'icon' => 'bi bi-people-fill',
                    'url' => ['prefix' => 'Parent', 'controller' => 'Dashboard', 'action' => 'children'],
                    'controller' => 'Dashboard',
                    'action' => 'children',
                ],
                [
                    'label' => 'Booking System',
                    'icon' => 'bi bi-palette',
                    'url' => ['prefix' => 'Parent', 'controller' => 'Courses', 'action' => 'index'],
                    'controller' => 'Courses',
                ],
                [
                    'label' => 'View Schedule & Attendance',
                    'icon' => 'bi bi-calendar-event',
                    'url' => ['prefix' => 'Parent', 'controller' => 'Bookings', 'action' => 'index'],
                    'controller' => 'Bookings',
                ],
                [
                    'label' => 'Payment Portal',
                    'icon' => 'bi bi-credit-card',
                    'url' => ['prefix' => 'Parent', 'controller' => 'Payments', 'action' => 'index'],
                    'controller' => 'Payments',
                ],
                [
                    'label' => 'Learning Resources',
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
