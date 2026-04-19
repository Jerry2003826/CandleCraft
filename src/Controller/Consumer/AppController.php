<?php
declare(strict_types=1);

namespace App\Controller\Consumer;

use App\Controller\AppController as BaseAppController;
use Cake\Event\EventInterface;
use Cake\Http\Response;

class AppController extends BaseAppController
{
    protected bool $bookingAccessEnabled = false;
    protected bool $ageVerifiedByAdmin = false;
    protected ?int $declaredAge = null;
    protected string $userRole = '';

    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        $identity = $this->Authentication->getIdentity();
        $this->userRole = $identity ? (string)$identity->get('user_role') : '';

        if (!$identity) {
            $this->shortCircuitRequest(
                $event,
                $this->rejectUnauthenticatedAccess('Please sign in with a student account to continue.')
            );

            return;
        }

        if ($this->userRole !== 'student') {
            $this->shortCircuitRequest(
                $event,
                $this->redirectAuthenticatedRoleMismatch($identity, 'Please sign in with a student account to continue.')
            );

            return;
        }

        $student = $this->fetchTable('Students')->find()
            ->contain(['Users'])
            ->where(['Students.user_id' => $identity->get('user_id')])
            ->first();

        if (!$student) {
            $this->Flash->error(__('Your student profile could not be found. Please contact an administrator.'));
            $this->Authentication->logout();
            $this->shortCircuitRequest(
                $event,
                $this->redirect(['prefix' => false, 'controller' => 'Users', 'action' => 'login'])
            );

            return;
        }

        $this->declaredAge = $this->determineDeclaredAge($student);
        $this->ageVerifiedByAdmin = (bool)($student->user?->age_verified_by_admin ?? false);
        $this->bookingAccessEnabled = $this->ageVerifiedByAdmin;
        $restrictedResponse = $this->enforceAgeRestrictions();
        if ($restrictedResponse !== null) {
            $this->shortCircuitRequest($event, $restrictedResponse);

            return;
        }

        $this->viewBuilder()->setLayout('portal');
        $this->set('portalContext', $this->buildPortalContext());
        $this->set('bookingAccessEnabled', $this->bookingAccessEnabled);
        $this->set('ageVerifiedByAdmin', $this->ageVerifiedByAdmin);
        $this->set('declaredAge', $this->declaredAge);
        $this->set('userRole', $this->userRole);
    }

    private function determineDeclaredAge(object $student): ?int
    {
        if ($student->declared_age !== null) {
            return (int)$student->declared_age;
        }

        if (!$student || !$student->date_of_birth) {
            return null;
        }

        $dob = $student->date_of_birth;
        $now = new \Cake\Chronos\ChronosDate();

        return (int)$dob->diff($now)->y;
    }

    private function enforceAgeRestrictions(): ?Response
    {
        $controller = $this->request->getParam('controller');
        $action = $this->request->getParam('action');

        $restricted = [
            'Bookings' => ['add'],
            'Payments' => ['index', 'process', 'success', 'cancel', 'receipt'],
        ];

        if (
            !$this->bookingAccessEnabled
            && isset($restricted[$controller])
            && in_array($action, $restricted[$controller], true)
        ) {
            $this->Flash->warning(__('Your adult verification is still pending. You can browse courses, but booking and payment stay locked until an administrator confirms you are 18 or older.'));
            return $this->redirect(['prefix' => 'Consumer', 'controller' => 'Dashboard', 'action' => 'index']);
        }

        return null;
    }

    private function buildPortalContext(): array
    {
        $nav = [
            [
                'label' => 'Dashboard',
                'icon' => 'bi bi-house',
                'url' => ['prefix' => 'Consumer', 'controller' => 'Dashboard', 'action' => 'index'],
                'controller' => 'Dashboard',
            ],
            [
                'label' => 'Booking System',
                'icon' => 'bi bi-palette',
                'url' => ['prefix' => 'Consumer', 'controller' => 'Courses', 'action' => 'index'],
                'controller' => 'Courses',
            ],
            [
                'label' => 'View Schedule & Attendance',
                'icon' => 'bi bi-calendar-event',
                'url' => ['prefix' => 'Consumer', 'controller' => 'Bookings', 'action' => 'index'],
                'controller' => 'Bookings',
            ],
            [
                'label' => 'Payment Portal',
                'icon' => 'bi bi-credit-card',
                'url' => ['prefix' => 'Consumer', 'controller' => 'Payments', 'action' => 'index'],
                'controller' => 'Payments',
            ],
            [
                'label' => 'Learning Resources',
            'icon' => 'bi bi-folder',
            'url' => ['prefix' => 'Consumer', 'controller' => 'Resources', 'action' => 'index'],
            'controller' => 'Resources',
            ],
            [
                'label' => 'Notifications',
                'icon' => 'bi bi-bell',
                'url' => ['prefix' => 'Consumer', 'controller' => 'Notifications', 'action' => 'index'],
                'controller' => 'Notifications',
            ],
        ];

        return [
            'title' => 'Customer Portal',
            'icon' => 'bi bi-person-circle',
            'welcome' => 'Customer Hub',
            'nav' => $nav,
        ];
    }
}
