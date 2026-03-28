<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Notification extends Entity
{
    protected array $_accessible = [
        'user_id' => true,
        'title' => true,
        'message' => true,
        'notification_type' => true,
        'is_read' => true,
        'user' => true,
    ];
}
