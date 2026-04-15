<?php
declare(strict_types=1);

namespace App\Controller\Consumer;

use Cake\Http\Response;

class NotificationsController extends AppController
{
    public function index(): void
    {
        $identity = $this->Authentication->getIdentity();
        $notificationsTable = $this->fetchTable('Notifications');

        $userIds = $this->getRelevantUserIds($identity);

        $notifications = $notificationsTable->find()
            ->where(['Notifications.user_id IN' => $userIds])
            ->order(['Notifications.created' => 'DESC'])
            ->limit(50)
            ->all();

        $this->set(compact('notifications'));
        $this->set('title', 'Notifications');
    }

    public function markRead(?int $notificationId = null): ?Response
    {
        $this->request->allowMethod(['post']);
        $identity = $this->Authentication->getIdentity();
        $notificationsTable = $this->fetchTable('Notifications');

        $userIds = $this->getRelevantUserIds($identity);

        $notification = $notificationsTable->find()
            ->where([
                'Notifications.id' => $notificationId,
                'Notifications.user_id IN' => $userIds,
            ])
            ->firstOrFail();

        $notification->is_read = true;
        $notificationsTable->save($notification);

        return $this->redirect(['action' => 'index']);
    }

    public function markAllRead(): ?Response
    {
        $this->request->allowMethod(['post']);
        $identity = $this->Authentication->getIdentity();
        $notificationsTable = $this->fetchTable('Notifications');

        $userIds = $this->getRelevantUserIds($identity);

        $notifications = $notificationsTable->find()
            ->where([
                'Notifications.user_id IN' => $userIds,
                'Notifications.is_read' => false,
            ])
            ->all();

        foreach ($notifications as $notification) {
            $notification->is_read = true;
            $notificationsTable->save($notification);
        }

        $this->Flash->success(__('All notifications marked as read.'));

        return $this->redirect(['action' => 'index']);
    }

    private function getRelevantUserIds($identity): array
    {
        return [$identity->get('user_id')];
    }
}
