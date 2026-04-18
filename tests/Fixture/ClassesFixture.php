<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class ClassesFixture extends TestFixture
{
    public function init(): void
    {
        $this->records = [
            [
                'class_id' => 1,
                'class_code' => 'POT-101',
                'course_id' => 1,
                'teacher_id' => 1,
                'start_datetime' => '2026-05-01 10:00:00',
                'end_datetime' => '2026-05-01 12:00:00',
                'location' => 'Studio A',
                'capacity' => 10,
                'class_status' => 'scheduled',
                'notes' => null,
                'created_at' => '2026-04-01 09:00:00',
                'updated_at' => '2026-04-01 09:00:00',
            ],
            [
                'class_id' => 2,
                'class_code' => 'KNI-201',
                'course_id' => 2,
                'teacher_id' => 2,
                'start_datetime' => '2026-05-02 10:00:00',
                'end_datetime' => '2026-05-02 12:00:00',
                'location' => 'Studio B',
                'capacity' => 10,
                'class_status' => 'scheduled',
                'notes' => null,
                'created_at' => '2026-04-01 09:00:00',
                'updated_at' => '2026-04-01 09:00:00',
            ],
        ];
        parent::init();
    }
}
