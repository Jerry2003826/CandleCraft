<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class SiteMediaFixture extends TestFixture
{
    public function init(): void
    {
        $now = '2026-05-11 09:00:00';
        $this->records = [
            [
                'id' => 1,
                'file_name' => 'logo.png',
                'file_path' => 'uploads/site/2026/05/abc123.png',
                'file_url' => '/uploads/site/2026/05/abc123.png',
                'mime_type' => 'image/png',
                'file_size' => 12345,
                'alt_text' => 'CandleCraft logo',
                'uploaded_by_id' => 1,
                'uploaded_at' => $now,
                'updated_at' => $now,
            ],
        ];
        parent::init();
    }
}
