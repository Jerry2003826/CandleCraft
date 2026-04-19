<?php
declare(strict_types=1);

namespace App\Controller\Parent;

use Cake\Http\Response;

class NotificationsController extends AppController
{
    public function index(): void
    {
        $identity = $this->Authentication->getIdentity();
        $parentsTable = $this->fetchTable('Parents');
        $parentStudentsTable = $this->fetchTable('ParentStudents');
        $notificationsTable = $this->fetchTable('Notifications');
        $studentsTable = $this->fetchTable('Students');

        $parent = $parentsTable->find()
            ->where(['Parents.user_id' => $identity?->get('user_id')])
            ->firstOrFail();

        $childUserIds = $parentStudentsTable->find()
            ->where(['ParentStudents.parent_id' => $parent->parent_id])
            ->all()
            ->map(function ($ps) use ($studentsTable) {
                $student = $studentsTable->find()
                    ->where(['Students.student_id' => $ps->student_id])
                    ->first();
                return $student ? $student->user_id : null;
            })
            ->filter()
            ->toArray();

        $userIds = array_merge([$identity->get('user_id')], array_values($childUserIds));

        $notifications = $notificationsTable->find()
            ->where(['Notifications.user_id IN' => $userIds])
            ->orderBy(['Notifications.created' => 'DESC'])
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

        $notification = $notificationsTable->find()
            ->where(['Notifications.id' => $notificationId])
            ->firstOrFail();

        $notification->is_read = true;
        $notificationsTable->save($notification);

        return $this->redirect(['action' => 'index']);
    }

    public function markAllRead(): ?Response
    {
        $this->request->allowMethod(['post']);
        $identity = $this->Authentication->getIdentity();
        $parentsTable = $this->fetchTable('Parents');
        $parentStudentsTable = $this->fetchTable('ParentStudents');
        $notificationsTable = $this->fetchTable('Notifications');
        $studentsTable = $this->fetchTable('Students');

        $parent = $parentsTable->find()
            ->where(['Parents.user_id' => $identity?->get('user_id')])
            ->firstOrFail();

        $childUserIds = $parentStudentsTable->find()
            ->where(['ParentStudents.parent_id' => $parent->parent_id])
            ->all()
            ->map(function ($ps) use ($studentsTable) {
                $student = $studentsTable->find()
                    ->where(['Students.student_id' => $ps->student_id])
                    ->first();
                return $student ? $student->user_id : null;
            })
            ->filter()
            ->toArray();

        $userIds = array_merge([$identity->get('user_id')], array_values($childUserIds));

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
}
