<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class PaymentsFixture extends TestFixture
{
    public function init(): void
    {
        $this->records = [
            [
                'payment_id' => 1,
                'booking_id' => 1,
                'amount' => 50.00,
                'currency_code' => 'AUD',
                'payment_date' => null,
                'payment_method' => 'online',
                'payment_status' => 'pending',
                'transaction_reference' => 'cs_owned',
                'refunded_amount' => 0.00,
                'notes' => null,
                'created_at' => '2026-04-10 10:00:00',
                'updated_at' => '2026-04-10 10:00:00',
            ],
            [
                'payment_id' => 2,
                'booking_id' => 2,
                'amount' => 65.00,
                'currency_code' => 'AUD',
                'payment_date' => null,
                'payment_method' => 'online',
                'payment_status' => 'pending',
                'transaction_reference' => 'cs_other',
                'refunded_amount' => 0.00,
                'notes' => null,
                'created_at' => '2026-04-10 10:30:00',
                'updated_at' => '2026-04-10 10:30:00',
            ],
        ];
        parent::init();
    }
}
