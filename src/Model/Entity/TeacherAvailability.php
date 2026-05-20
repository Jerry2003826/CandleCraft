<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class TeacherAvailability extends Entity
{
    protected array $_accessible = [
        'teacher_id' => true,
        'day_of_week' => true,
        'start_time' => true,
        'end_time' => true,
        'valid_from' => true,
        'valid_until' => true,
        'is_available' => true,
        'teacher' => true,
    ];
}
