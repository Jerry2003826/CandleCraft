<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\ORM\TableRegistry;

class DashboardController extends AppController
{
    public function index(): void
    {
        $messagesTable = $this->fetchTable('Messages');

        $totalEnquiries = $messagesTable->find()->count();
        $newMessages = $messagesTable->find()->where(['message_status' => 'unread'])->count();
        $repliedMessages = $messagesTable->find()->where(['message_status' => 'replied'])->count();

        $recentMessages = $messagesTable->find()
            ->contain(['SenderUsers'])
            ->order(['Messages.sent_at' => 'DESC'])
            ->limit(10)
            ->all();

        $this->set(compact('totalEnquiries', 'newMessages', 'repliedMessages', 'recentMessages'));
    }
}
