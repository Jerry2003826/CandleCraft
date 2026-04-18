<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class TeachersFixture extends TestFixture
{
    public function init(): void
    {
        $this->records = [
            [
                'teacher_id' => 1,
                'user_id' => 2,
                'teacher_name' => 'Teacher One',
                'phone_number' => '0400000001',
                'specialization' => 'pottery',
                'teacher_status' => 'active',
                'hire_date' => '2025-01-01',
                'created_at' => '2026-04-01 09:00:00',
                'updated_at' => '2026-04-01 09:00:00',
            ],
            [
                'teacher_id' => 2,
                'user_id' => 3,
                'teacher_name' => 'Teacher Two',
                'phone_number' => '0400000002',
                'specialization' => 'knitting',
                'teacher_status' => 'active',
                'hire_date' => '2025-01-01',
                'created_at' => '2026-04-01 09:00:00',
                'updated_at' => '2026-04-01 09:00:00',
            ],
        ];
        parent::init();
    }
}
