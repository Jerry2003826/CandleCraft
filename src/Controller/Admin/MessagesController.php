<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\I18n\DateTime;

class MessagesController extends AppController
{
    public function index(): void
    {
        $messagesTable = $this->fetchTable('Messages');
        $query = $messagesTable->find()
            ->contain(['SenderUsers', 'ReceiverUsers'])
            ->where(['Messages.message_type' => 'contact_form'])
            ->order(['Messages.sent_at' => 'DESC']);

        $status = $this->request->getQuery('status');
        if ($status && in_array($status, ['unread', 'read', 'replied', 'archived'])) {
            $query->where(['Messages.message_status' => $status]);
        }

        $messages = $this->paginate($query, ['limit' => 20]);

        $this->set(compact('messages', 'status'));
    }

    public function view(?string $id = null): void
    {
        $messagesTable = $this->fetchTable('Messages');
        $message = $messagesTable->get($id, contain: ['SenderUsers', 'ReceiverUsers']);

        if ($message->message_status === 'unread') {
            $message->message_status = 'read';
            $messagesTable->save($message);
        }

        $nullSafe = function (string $field, $value): array {
            return $value !== null ? [$field => $value] : [$field . ' IS' => null];
        };

        $replies = $messagesTable->find()
            ->where([
                'Messages.subject LIKE' => 'Re: %',
                'OR' => [
                    array_merge(
                        $nullSafe('Messages.sender_user_id', $message->receiver_user_id),
                        $nullSafe('Messages.receiver_user_id', $message->sender_user_id)
                    ),
                    array_merge(
                        $nullSafe('Messages.sender_user_id', $message->sender_user_id),
                        $nullSafe('Messages.receiver_user_id', $message->receiver_user_id)
                    ),
                ],
            ])
            ->contain(['SenderUsers'])
            ->order(['Messages.sent_at' => 'ASC'])
            ->all();

        $this->set(compact('message', 'replies'));
    }

    public function reply(?string $id = null)
    {
        $messagesTable = $this->fetchTable('Messages');
        $originalMessage = $messagesTable->get($id, contain: ['SenderUsers']);

        if ($this->request->is('post')) {
            $identity = $this->Authentication->getIdentity();
            $replyData = [
                'sender_user_id' => $identity->get('user_id'),
                'receiver_user_id' => $originalMessage->sender_user_id,
                'subject' => 'Re: ' . $originalMessage->subject,
                'message_text' => $this->request->getData('message_text'),
                'message_type' => 'internal',
                'message_status' => 'unread',
                'sent_at' => DateTime::now(),
            ];

            $reply = $messagesTable->newEntity($replyData);
            if ($messagesTable->save($reply)) {
                $originalMessage->message_status = 'replied';
                $messagesTable->save($originalMessage);

                $this->Flash->success(__('Reply sent successfully.'));

                return $this->redirect(['action' => 'view', $id]);
            }
            $this->Flash->error(__('Failed to send reply. Please try again.'));
        }

        $this->set(compact('originalMessage'));
    }

    public function delete(?string $id = null)
    {
        $this->request->allowMethod(['post', 'delete']);

        $messagesTable = $this->fetchTable('Messages');
        $message = $messagesTable->get($id);
        if ($messagesTable->delete($message)) {
            $this->Flash->success(__('The message has been deleted.'));
        } else {
            $this->Flash->error(__('The message could not be deleted. Please try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
