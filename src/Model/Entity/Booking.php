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
        'reminder_sent_at' => true,
        'booking_confirmation_sent_at' => true,
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

    /**
     * Get class entity.
     *
     * @return mixed
     */
    protected function _getClassEntity(): mixed
    {
        return $this->get('class');
    }

    /**
     * Get parent entity.
     *
     * @return mixed
     */
    protected function _getParentEntity(): mixed
    {
        return $this->get('parent');
    }

    /**
     * Get attendance records.
     *
     * @return mixed
     */
    protected function _getAttendanceRecords(): mixed
    {
        return $this->get('attendance_record');
    }
}
