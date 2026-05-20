<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class ClassEntity extends Entity
{
    protected array $_accessible = [
        'class_name' => true,
        'class_code' => true,
        'course_id' => true,
        'teacher_id' => true,
        'start_datetime' => true,
        'end_datetime' => true,
        'location' => true,
        'capacity' => true,
        'class_status' => true,
        'notes' => true,
        'created_at' => true,
        'updated_at' => true,
        'course' => true,
        'teacher' => true,
        'bookings' => true,
        'learning_resources' => true,
    ];
}
