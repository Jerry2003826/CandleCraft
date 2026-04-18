<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class LearningResourcesFixture extends TestFixture
{
    public function init(): void
    {
        $this->records = [
            [
                'resource_id' => 1,
                'class_id' => 1,
                'uploaded_by_teacher_id' => 1,
                'resource_name' => 'Wheel Guide',
                'resource_type' => 'document',
                'resource_url' => 'https://example.com/resources/wheel-guide',
                'file_path' => null,
                'resource_description' => 'Fixture resource',
                'resource_status' => 'active',
                'uploaded_at' => '2026-04-10 11:00:00',
            ],
        ];
        parent::init();
    }
}
