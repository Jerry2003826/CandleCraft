<?php
declare(strict_types=1);

namespace App\Controller\Consumer;

use App\Controller\AppController as BaseAppController;
use Cake\Event\EventInterface;

class AppController extends BaseAppController
{
    protected bool $isAdult = false;
    protected bool $ageVerifiedByAdmin = false;
    protected string $userRole = '';

    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        $identity = $this->Authentication->getIdentity();
        $this->userRole = $identity ? (string)$identity->get('user_role') : '';

        if (!$identity || !in_array($this->userRole, ['student', 'parent'], true)) {
            $this->Flash->error(__('Please sign in with a student or parent account to continue.'));
            $this->Authentication->logout();
            $event->stopPropagation();
            $this->setResponse($this->redirect(['prefix' => false, 'controller' => 'Users', 'action' => 'login']));

            return;
        }

        $this->isAdult = $this->determineIsAdult($identity);
        $this->ageVerifiedByAdmin = (bool)$identity->get('age_verified_by_admin');
        $this->enforceAgeRestrictions($event);

        $this->viewBuilder()->setLayout('portal');
        $this->set('portalContext', $this->buildPortalContext());
        $this->set('isAdult', $this->isAdult);
        $this->set('ageVerifiedByAdmin', $this->ageVerifiedByAdmin);
        $this->set('userRole', $this->userRole);
    }

    private function determineIsAdult($identity): bool
    {
        if ($this->userRole === 'parent') {
            return true;
        }

        $studentsTable = $this->fetchTable('Students');
        $student = $studentsTable->find()
            ->where(['Students.user_id' => $identity->get('user_id')])
            ->first();

        if (!$student || !$student->date_of_birth) {
            return false;
        }

        $dob = $student->date_of_birth;
        $now = new \Cake\Chronos\ChronosDate();
        $age = (int)$dob->diff($now)->y;

        return $age >= 18;
    }

    /**
     * Block under-18 users from booking write operations.
     * Block unverified users from payment operations (admin must confirm age first).
     */
    private function enforceAgeRestrictions(EventInterface $event): void
    {
        $controller = $this->request->getParam('controller');
        $action = $this->request->getParam('action');

        if (!$this->isAdult) {
            $restricted = [
                'Bookings' => ['add', 'cancel'],
                'Payments' => ['index', 'process', 'success', 'cancel', 'webhook', 'receipt'],
            ];

            if (isset($restricted[$controller]) && in_array($action, $restricted[$controller], true)) {
                $this->Flash->error(__('You must be 18 or older to access booking and payment features.'));
                $event->stopPropagation();
                $this->setResponse($this->redirect(['prefix' => 'Consumer', 'controller' => 'Dashboard', 'action' => 'index']));

                return;
            }
        }

        if (!$this->ageVerifiedByAdmin && $controller === 'Payments' && in_array($action, ['process', 'success'], true)) {
            $this->Flash->warning(__('Your age has not been verified by an administrator yet. Payment is not available until admin approval.'));
            $event->stopPropagation();
            $this->setResponse($this->redirect(['prefix' => 'Consumer', 'controller' => 'Bookings', 'action' => 'index']));
        }
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
                'label' => 'Browse Courses',
                'icon' => 'bi bi-palette',
                'url' => ['prefix' => 'Consumer', 'controller' => 'Courses', 'action' => 'index'],
                'controller' => 'Courses',
            ],
            [
                'label' => 'My Schedule',
                'icon' => 'bi bi-calendar-event',
                'url' => ['prefix' => 'Consumer', 'controller' => 'Bookings', 'action' => 'index'],
                'controller' => 'Bookings',
            ],
        ];

        if ($this->isAdult) {
            $nav[] = [
                'label' => 'Payments',
                'icon' => 'bi bi-credit-card',
                'url' => ['prefix' => 'Consumer', 'controller' => 'Payments', 'action' => 'index'],
                'controller' => 'Payments',
            ];
        }

        $nav[] = [
            'label' => 'Learning Center',
            'icon' => 'bi bi-folder',
            'url' => ['prefix' => 'Consumer', 'controller' => 'Resources', 'action' => 'index'],
            'controller' => 'Resources',
        ];

        $nav[] = [
            'label' => 'Notifications',
            'icon' => 'bi bi-bell',
            'url' => ['prefix' => 'Consumer', 'controller' => 'Notifications', 'action' => 'index'],
            'controller' => 'Notifications',
        ];

        return [
            'title' => 'Consumer Portal',
            'icon' => 'bi bi-person-circle',
            'welcome' => $this->userRole === 'parent' ? 'Family Hub' : 'Learning Hub',
            'nav' => $nav,
        ];
    }
}
