<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class ParentStudentsFixture extends TestFixture
{
    public function init(): void
    {
        $this->records = [
            [
                'parent_id' => 1,
                'student_id' => 1,
                'relationship_to_student' => 'mother',
                'is_primary_guardian' => true,
                'can_pick_up' => true,
                'created_at' => '2026-04-01 09:00:00',
            ],
        ];

        parent::init();
    }
}
