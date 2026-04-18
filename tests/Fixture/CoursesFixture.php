<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class CoursesFixture extends TestFixture
{
    public function init(): void
    {
        $this->records = [
            [
                'course_id' => 1,
                'course_name' => 'Beginner Pottery',
                'course_type' => 'pottery',
                'course_level' => 'beginner',
                'course_price' => 50.00,
                'course_description' => 'Intro pottery course',
                'is_active' => true,
                'created_at' => '2026-04-01 09:00:00',
                'updated_at' => '2026-04-01 09:00:00',
            ],
            [
                'course_id' => 2,
                'course_name' => 'Knitting Basics',
                'course_type' => 'knitting',
                'course_level' => 'beginner',
                'course_price' => 65.00,
                'course_description' => 'Intro knitting course',
                'is_active' => true,
                'created_at' => '2026-04-01 09:00:00',
                'updated_at' => '2026-04-01 09:00:00',
            ],
        ];
        parent::init();
    }
}
