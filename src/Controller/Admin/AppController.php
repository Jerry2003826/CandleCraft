<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController as BaseAppController;
use Cake\Event\EventInterface;

class AppController extends BaseAppController
{
    /**
     * Before filter.
     *
     * @param mixed $event Event.
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        $identity = $this->Authentication->getIdentity();
        if (!$identity) {
            $this->shortCircuitRequest(
                $event,
                $this->rejectUnauthenticatedAccess('You do not have permission to access the admin area.'),
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
                $this->redirectAuthenticatedRoleMismatch($currentUser, 'You do not have permission to access the admin area.'),
            );

            return;
        }

        $this->viewBuilder()->setLayout('admin');
    }

    /**
     * Before render.
     *
     * @param mixed $event Event.
     */
    public function beforeRender(EventInterface $event): void
    {
        parent::beforeRender($event);

        $messagesTable = $this->fetchTable('Messages');
        $pendingAccountRequestConditions = [
            'Messages.message_type' => 'contact_form',
            'Messages.message_status IN' => ['unread', 'read'],
            'OR' => [
                ['Messages.source_page' => 'account-request'],
                ['Messages.message_text LIKE' => '%[REQUEST TYPE: account_access]%'],
                ['Messages.message_text LIKE' => '%[REQUEST TYPE: customer_access]%'],
            ],
        ];

        $adminPendingAccountRequestCount = $messagesTable->find()
            ->where($pendingAccountRequestConditions)
            ->count();
        $adminPendingAccountRequests = $messagesTable->find()
            ->where($pendingAccountRequestConditions)
            ->orderBy(['Messages.sent_at' => 'DESC'])
            ->limit(5)
            ->all()
            ->toList();

        $this->set(compact('adminPendingAccountRequestCount', 'adminPendingAccountRequests'));
    }
}
