<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Admin extends Entity
{
    protected array $_accessible = [
        'user_id' => true,
        'admin_name' => true,
        'is_super_admin' => true,
        'notes' => true,
        'created_at' => true,
        'updated_at' => true,
        'user' => true,
    ];
}
