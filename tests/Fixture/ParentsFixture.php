<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class ParentsFixture extends TestFixture
{
    public function init(): void
    {
        $this->records = [
            [
                'parent_id' => 1,
                'user_id' => 6,
                'parent_name' => 'Parent One',
                'phone_number' => '0400000006',
                'emergency_contact_name' => 'Backup Parent',
                'emergency_contact_phone' => '0400000999',
                'address' => '1 Family Street',
                'created_at' => '2026-04-01 09:00:00',
                'updated_at' => '2026-04-01 09:00:00',
            ],
        ];

        parent::init();
    }
}
