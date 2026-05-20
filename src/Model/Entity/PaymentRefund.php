<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class PaymentRefund extends Entity
{
    protected array $_accessible = [
        'payment_id' => true,
        'stripe_refund_id' => true,
        'stripe_charge_id' => true,
        'stripe_payment_intent_id' => true,
        'amount' => true,
        'currency_code' => true,
        'status' => true,
        'reason' => true,
        'initiated_by_admin_id' => true,
        'failure_message' => true,
        'raw_payload' => true,
        'payment' => true,
        'initiated_by_admin' => true,
    ];
}
