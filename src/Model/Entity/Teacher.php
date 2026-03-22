<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Teacher extends Entity
{
    protected array $_accessible = [
        'user_id' => true,
        'teacher_name' => true,
        'phone_number' => true,
        'specialization' => true,
        'teacher_status' => true,
        'hire_date' => true,
        'created_at' => true,
        'updated_at' => true,
        'user' => true,
        'classes' => true,
    ];
}
