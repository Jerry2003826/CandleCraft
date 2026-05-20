<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class SitePagesFixture extends TestFixture
{
    public function init(): void
    {
        $now = '2026-05-11 09:00:00';
        $this->records = [
            ['id' => 1, 'page_slug' => 'global', 'page_title' => 'Global / Brand', 'is_active' => true, 'sort_order' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'page_slug' => 'home', 'page_title' => 'Home Page', 'is_active' => true, 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'page_slug' => 'contact', 'page_title' => 'Contact / Enquiry', 'is_active' => true, 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'page_slug' => 'inactive', 'page_title' => 'Inactive Page', 'is_active' => false, 'sort_order' => 9, 'created_at' => $now, 'updated_at' => $now],
        ];
        parent::init();
    }
}
