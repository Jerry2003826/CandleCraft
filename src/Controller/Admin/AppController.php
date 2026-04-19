<?php
declare(strict_types=1);

namespace App\Controller\Admin;

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
                $this->rejectUnauthenticatedAccess('You do not have permission to access the admin area.')
            );

            return;
        }

        $currentUser = $this->loadActiveAuthenticatedUser($event, $identity);
        if ($currentUser === null) {
            return;
        }

        if ((string)$currentUser->get('user_role') !== 'admin') {
            $this->shortCircuitRequest(
                $event,
                $this->redirectAuthenticatedRoleMismatch($currentUser, 'You do not have permission to access the admin area.')
            );

            return;
        }

        $this->viewBuilder()->setLayout('admin');
    }
}
