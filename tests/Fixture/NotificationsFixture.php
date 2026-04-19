<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class NotificationsFixture extends TestFixture
{
    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'user_id' => 6,
                'title' => 'Parent notification',
                'message' => 'Parent-specific update.',
                'notification_type' => 'system',
                'is_read' => false,
                'created' => '2026-04-15 10:00:00',
            ],
            [
                'id' => 2,
                'user_id' => 4,
                'title' => 'Child notification',
                'message' => 'Child-specific update.',
                'notification_type' => 'booking_confirmation',
                'is_read' => false,
                'created' => '2026-04-15 11:00:00',
            ],
            [
                'id' => 3,
                'user_id' => 5,
                'title' => 'Unrelated student notification',
                'message' => 'Should remain untouched.',
                'notification_type' => 'system',
                'is_read' => false,
                'created' => '2026-04-15 12:00:00',
            ],
            [
                'id' => 4,
                'user_id' => 1,
                'title' => 'Admin notification',
                'message' => 'Admin-only update.',
                'notification_type' => 'system',
                'is_read' => false,
                'created' => '2026-04-15 13:00:00',
            ],
        ];

        parent::init();
    }
}
