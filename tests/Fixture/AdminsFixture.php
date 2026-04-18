<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class AdminsFixture extends TestFixture
{
    public function init(): void
    {
        $this->records = [
            [
                'admin_id' => 1,
                'user_id' => 1,
                'admin_name' => 'Admin User',
                'is_super_admin' => true,
                'notes' => 'Fixture admin',
                'created_at' => '2026-04-01 09:00:00',
                'updated_at' => '2026-04-01 09:00:00',
            ],
        ];
        parent::init();
    }
}
