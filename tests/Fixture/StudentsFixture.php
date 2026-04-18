<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class StudentsFixture extends TestFixture
{
    public function init(): void
    {
        $this->records = [
            [
                'student_id' => 1,
                'user_id' => 4,
                'student_name' => 'Student One',
                'declared_age' => 21,
                'date_of_birth' => '2004-01-01',
                'student_status' => 'active',
                'medical_notes' => null,
                'created_at' => '2026-04-01 09:00:00',
                'updated_at' => '2026-04-01 09:00:00',
            ],
            [
                'student_id' => 2,
                'user_id' => 5,
                'student_name' => 'Student Two',
                'declared_age' => 22,
                'date_of_birth' => '2003-01-01',
                'student_status' => 'active',
                'medical_notes' => null,
                'created_at' => '2026-04-01 09:00:00',
                'updated_at' => '2026-04-01 09:00:00',
            ],
        ];
        parent::init();
    }
}
