<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class AttendanceRecord extends Entity
{
    protected array $_accessible = [
        'booking_id' => true,
        'marked_by_teacher_id' => true,
        'attendance_date' => true,
        'attendance_status' => true,
        'attendance_notes' => true,
        'created_at' => true,
        'updated_at' => true,
        'booking' => true,
        'teacher' => true,
    ];
}
