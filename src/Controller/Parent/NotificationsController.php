<?php
declare(strict_types=1);

namespace App\Controller\Parent;

use Cake\Http\Response;

class NotificationsController extends AppController
{
    /**
     * Index.
     */
    public function index(): void
    {
        $notificationsTable = $this->fetchTable('Notifications');
        $userIds = $this->getAllowedNotificationUserIds();

        $notifications = $notificationsTable->find()
            ->where(['Notifications.user_id IN' => $userIds])
            ->orderBy(['Notifications.created' => 'DESC'])
            ->limit(50)
            ->all();

        $this->set(compact('notifications'));
        $this->set('title', 'Notifications');
    }

    /**
     * Mark read.
     *
     * @param mixed $notificationId Notificationid.
     */
    public function markRead(?int $notificationId = null): ?Response
    {
        $this->request->allowMethod(['post']);
        $notificationsTable = $this->fetchTable('Notifications');
        $userIds = $this->getAllowedNotificationUserIds();

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

    /**
     * Mark all read.
     */
    public function markAllRead(): ?Response
    {
        $this->request->allowMethod(['post']);
        $notificationsTable = $this->fetchTable('Notifications');
        $userIds = $this->getAllowedNotificationUserIds();

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

    /**
     * @return list<int>
     */
    private function getAllowedNotificationUserIds(): array
    {
        $identity = $this->Authentication->getIdentity();
        $parent = $this->fetchTable('Parents')->find()
            ->where(['Parents.user_id' => $identity?->get('user_id')])
            ->firstOrFail();

        $childUserIds = $this->fetchTable('ParentStudents')->find()
            ->where(['ParentStudents.parent_id' => $parent->parent_id])
            ->contain(['Students'])
            ->all()
            ->map(fn($parentStudent) => $parentStudent->student?->user_id)
            ->filter(fn($userId) => is_numeric($userId))
            ->map(fn($userId) => (int)$userId)
            ->toList();

        $userIds = array_merge([(int)$identity?->get('user_id')], $childUserIds);

        return array_values(array_unique(array_filter($userIds, fn($userId) => $userId > 0)));
    }
}
