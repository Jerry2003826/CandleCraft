<?php
declare(strict_types=1);

namespace App\Controller\Parent;

use App\Controller\AppController as BaseAppController;
use App\Service\CustomerAccessPolicy;
use Cake\Event\EventInterface;
use Cake\Http\Response;

class AppController extends BaseAppController
{
    protected bool $bookingAccessEnabled = false;
    protected bool $paymentAccessEnabled = false;
    protected bool $ageVerifiedByAdmin = false;

    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        $identity = $this->Authentication->getIdentity();
        $accessPolicy = new CustomerAccessPolicy();
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

        $parent = $this->fetchTable('Parents')->find()
            ->contain(['Users'])
            ->where([
                'Parents.user_id' => $identity->get('user_id'),
            ])
            ->first();
        if (!$parent) {
            $this->Flash->error(__('Your parent profile could not be found. Please contact an administrator.'));
            $this->Authentication->logout();
            $this->shortCircuitRequest(
                $event,
                $this->redirect(['plugin' => false, 'prefix' => false, 'controller' => 'Users', 'action' => 'login'])
            );

            return;
        }

        $currentUser = $parent->user ?? null;
        $this->ageVerifiedByAdmin = $accessPolicy->isAdultConfirmed($identity, $currentUser);
        $this->bookingAccessEnabled = $accessPolicy->canBook($identity, $currentUser);
        $this->paymentAccessEnabled = $accessPolicy->canPay($identity, $currentUser);
        $restrictedResponse = $this->enforceAgeRestrictions();
        if ($restrictedResponse !== null) {
            $this->shortCircuitRequest($event, $restrictedResponse);

            return;
        }

        $this->viewBuilder()->setLayout('portal');
        $portalContext = [
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
                    'label' => 'View Schedule & Attendance',
                    'icon' => 'bi bi-calendar-event',
                    'url' => ['prefix' => 'Parent', 'controller' => 'Bookings', 'action' => 'index'],
                    'controller' => 'Bookings',
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
        ];
        if ($this->bookingAccessEnabled) {
            array_splice($portalContext['nav'], 2, 0, [[
                'label' => 'Booking System',
                'icon' => 'bi bi-palette',
                'url' => ['prefix' => 'Parent', 'controller' => 'Courses', 'action' => 'index'],
                'controller' => 'Courses',
            ]]);
        }
        if ($this->paymentAccessEnabled) {
            array_splice($portalContext['nav'], 4, 0, [[
                'label' => 'Payment Portal',
                'icon' => 'bi bi-credit-card',
                'url' => ['prefix' => 'Parent', 'controller' => 'Payments', 'action' => 'index'],
                'controller' => 'Payments',
            ]]);
        }
        $this->set('portalContext', $portalContext);
        $this->set('bookingAccessEnabled', $this->bookingAccessEnabled);
        $this->set('paymentAccessEnabled', $this->paymentAccessEnabled);
        $this->set('ageVerifiedByAdmin', $this->ageVerifiedByAdmin);
    }

    private function enforceAgeRestrictions(): ?Response
    {
        $controller = $this->request->getParam('controller');
        $action = $this->request->getParam('action');

        $blocked = false;
        if ($controller === 'Bookings' && in_array($action, ['add', 'cancel'], true)) {
            $blocked = !$this->bookingAccessEnabled;
        }

        if ($controller === 'Payments') {
            $blocked = !$this->paymentAccessEnabled;
        }

        if ($blocked) {
            $this->Flash->warning(__('Your adult verification is still pending. You can manage linked students, schedules, and learning resources now. Booking and payment will unlock after an administrator confirms you are 18 or older.'));

            return $this->redirect(['prefix' => 'Parent', 'controller' => 'Dashboard', 'action' => 'index']);
        }

        return null;
    }
}
