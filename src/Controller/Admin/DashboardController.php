<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\ORM\TableRegistry;

class DashboardController extends AppController
{
    public function index(): void
    {
        $messagesTable = $this->fetchTable('Messages');
        $enquiryConditions = ['Messages.message_type' => 'contact_form'];

        $totalEnquiries = $messagesTable->find()->where($enquiryConditions)->count();
        $newMessages = $messagesTable->find()->where($enquiryConditions + ['Messages.message_status' => 'unread'])->count();
        $repliedMessages = $messagesTable->find()->where($enquiryConditions + ['Messages.message_status' => 'replied'])->count();

        $recentMessages = $messagesTable->find()
            ->contain(['SenderUsers'])
            ->where($enquiryConditions)
            ->order(['Messages.sent_at' => 'DESC'])
            ->limit(10)
            ->all();

        $this->set(compact('totalEnquiries', 'newMessages', 'repliedMessages', 'recentMessages'));
    }
}
