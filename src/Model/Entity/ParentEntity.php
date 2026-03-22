<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class ParentEntity extends Entity
{
    protected array $_accessible = [
        'user_id' => true,
        'parent_name' => true,
        'phone_number' => true,
        'emergency_contact_name' => true,
        'emergency_contact_phone' => true,
        'address' => true,
        'created_at' => true,
        'updated_at' => true,
        'user' => true,
        'parent_students' => true,
    ];
}
