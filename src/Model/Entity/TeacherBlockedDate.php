<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class TeacherBlockedDate extends Entity
{
    protected array $_accessible = [
        'teacher_id' => true,
        'blocked_date' => true,
        'reason' => true,
        'teacher' => true,
    ];
}
