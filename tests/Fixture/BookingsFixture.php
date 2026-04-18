<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class BookingsFixture extends TestFixture
{
    public function init(): void
    {
        $this->records = [
            [
                'booking_id' => 1,
                'class_id' => 1,
                'parent_id' => null,
                'student_id' => 1,
                'booking_date' => '2026-04-10 09:00:00',
                'booking_status' => 'pending',
                'price_at_booking' => 50.00,
                'notes' => null,
                'created_at' => '2026-04-10 09:00:00',
                'updated_at' => '2026-04-10 09:00:00',
                'reminder_sent_at' => null,
            ],
            [
                'booking_id' => 2,
                'class_id' => 2,
                'parent_id' => null,
                'student_id' => 2,
                'booking_date' => '2026-04-10 09:30:00',
                'booking_status' => 'pending',
                'price_at_booking' => 65.00,
                'notes' => null,
                'created_at' => '2026-04-10 09:30:00',
                'updated_at' => '2026-04-10 09:30:00',
                'reminder_sent_at' => null,
            ],
        ];
        parent::init();
    }
}
