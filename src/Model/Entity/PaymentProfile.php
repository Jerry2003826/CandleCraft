<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class PaymentProfile extends Entity
{
    protected array $_accessible = [
        'user_id' => true,
        'billing_name' => true,
        'billing_email' => true,
        'billing_phone' => true,
        'billing_address_line1' => true,
        'billing_address_line2' => true,
        'billing_city' => true,
        'billing_state' => true,
        'billing_postcode' => true,
        'billing_country' => true,
        'preferred_payment_method' => true,
        'profile_status' => true,
        'is_default' => true,
        'created_at' => true,
        'updated_at' => true,
        'user' => true,
    ];
}
