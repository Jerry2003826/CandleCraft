<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Booking extends Entity
{
    protected array $_accessible = [
        'class_id' => true,
        'parent_id' => true,
        'student_id' => true,
        'booking_date' => true,
        'booking_status' => true,
        'price_at_booking' => true,
        'notes' => true,
        'created_at' => true,
        'updated_at' => true,
        'class' => true,
        'class_entity' => true,
        'student' => true,
        'parent' => true,
        'parent_entity' => true,
        'payments' => true,
        'attendance_record' => true,
        'attendance_records' => true,
    ];

    protected function _getClassEntity()
    {
        return $this->get('class');
    }

    protected function _getParentEntity()
    {
        return $this->get('parent');
    }

    protected function _getAttendanceRecords()
    {
        return $this->get('attendance_record');
    }
}
