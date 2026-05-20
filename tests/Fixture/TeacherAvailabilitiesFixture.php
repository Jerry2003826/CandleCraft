<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class TeacherAvailabilitiesFixture extends TestFixture
{
    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'teacher_id' => 1,
                'day_of_week' => 1,
                'start_time' => '09:00:00',
                'end_time' => '11:00:00',
                'valid_from' => '2026-05-01',
                'valid_until' => '2026-08-31',
                'is_available' => true,
                'created' => '2026-04-10 11:00:00',
                'modified' => '2026-04-10 11:00:00',
            ],
        ];
        parent::init();
    }
}
