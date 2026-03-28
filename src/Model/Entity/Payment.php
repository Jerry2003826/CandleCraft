<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Payment extends Entity
{
    protected array $_accessible = [
        'booking_id' => true,
        'amount' => true,
        'currency_code' => true,
        'payment_date' => true,
        'payment_method' => true,
        'payment_status' => true,
        'transaction_reference' => true,
        'refunded_amount' => true,
        'notes' => true,
        'booking' => true,
    ];
}
