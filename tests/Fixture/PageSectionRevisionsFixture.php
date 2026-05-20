<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class PageSectionRevisionsFixture extends TestFixture
{
    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'section_id' => 4,
                'content_value_snapshot' => 'Old Title',
                'media_id_snapshot' => null,
                'changed_by_id' => 1,
                'changed_at' => '2026-05-10 12:00:00',
                'change_summary' => 'Manual edit',
            ],
        ];
        parent::init();
    }
}
