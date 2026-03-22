<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Student extends Entity
{
    protected array $_accessible = [
        'user_id' => true,
        'student_name' => true,
        'date_of_birth' => true,
        'student_status' => true,
        'medical_notes' => true,
        'created_at' => true,
        'updated_at' => true,
        'user' => true,
        'parent_students' => true,
        'bookings' => true,
    ];
}
