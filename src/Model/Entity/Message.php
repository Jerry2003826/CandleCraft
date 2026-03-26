<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Message extends Entity
{
    protected array $_accessible = [
        'sender_user_id' => true,
        'receiver_user_id' => true,
        'sender_name' => true,
        'sender_email' => true,
        'sender_phone' => true,
        'source_page' => true,
        'subject' => true,
        'message_text' => true,
        'message_type' => true,
        'message_status' => true,
        'sent_at' => true,
        'updated_at' => true,
        'sender_user' => true,
        'receiver_user' => true,
    ];
}
