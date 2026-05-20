<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class PageSectionsFixture extends TestFixture
{
    public function init(): void
    {
        $now = '2026-05-11 09:00:00';
        $this->records = [
            [
                'id' => 1, 'page_id' => 1, 'section_key' => 'branding.site_name',
                'section_label' => 'Site Name', 'section_hint' => null,
                'content_type' => 'text', 'content_value' => 'CandleCraft Academy',
                'media_id' => null, 'is_active' => true, 'sort_order' => 1,
                'updated_at' => $now, 'updated_by_id' => null,
            ],
            [
                'id' => 2, 'page_id' => 1, 'section_key' => 'branding.copyright_text',
                'section_label' => 'Footer', 'section_hint' => 'Use {year} placeholder.',
                'content_type' => 'text', 'content_value' => '© {year} CandleCraft Academy.',
                'media_id' => null, 'is_active' => true, 'sort_order' => 2,
                'updated_at' => $now, 'updated_by_id' => null,
            ],
            [
                'id' => 3, 'page_id' => 1, 'section_key' => 'branding.logo_image',
                'section_label' => 'Logo', 'section_hint' => null,
                'content_type' => 'image', 'content_value' => null,
                'media_id' => 1, 'is_active' => true, 'sort_order' => 3,
                'updated_at' => $now, 'updated_by_id' => null,
            ],
            [
                'id' => 4, 'page_id' => 2, 'section_key' => 'hero.title',
                'section_label' => 'Hero Title', 'section_hint' => null,
                'content_type' => 'text', 'content_value' => 'CandleCraft Academy',
                'media_id' => null, 'is_active' => true, 'sort_order' => 1,
                'updated_at' => $now, 'updated_by_id' => null,
            ],
            [
                'id' => 5, 'page_id' => 2, 'section_key' => 'hero.eyebrow',
                'section_label' => 'Hero Eyebrow', 'section_hint' => null,
                'content_type' => 'text', 'content_value' => 'A Sanctuary For The Creative Soul.',
                'media_id' => null, 'is_active' => false, 'sort_order' => 2,
                'updated_at' => $now, 'updated_by_id' => null,
            ],
            [
                'id' => 6, 'page_id' => 3, 'section_key' => 'intro.body',
                'section_label' => 'Intro paragraph', 'section_hint' => null,
                'content_type' => 'html', 'content_value' => '<p>Welcome <strong>everyone</strong>.</p>',
                'media_id' => null, 'is_active' => true, 'sort_order' => 1,
                'updated_at' => $now, 'updated_by_id' => null,
            ],
        ];
        parent::init();
    }
}
