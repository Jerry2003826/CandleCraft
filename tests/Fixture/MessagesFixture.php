<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class MessagesFixture extends TestFixture
{
    public function init(): void
    {
        $this->records = [
            [
                'message_id' => 1,
                'sender_user_id' => null,
                'receiver_user_id' => null,
                'parent_message_id' => null,
                'sender_name' => 'Existing Message',
                'sender_email' => 'existing@example.com',
                'recipient_name' => null,
                'recipient_email' => null,
                'sender_phone' => '0400000000',
                'source_page' => 'contact-page',
                'subject' => 'Existing enquiry',
                'message_text' => 'Existing message body',
                'message_type' => 'contact_form',
                'message_status' => 'unread',
                'delivery_status' => null,
                'sent_at' => '2026-04-10 12:00:00',
                'updated_at' => '2026-04-10 12:00:00',
            ],
        ];
        parent::init();
    }
}
