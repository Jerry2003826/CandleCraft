<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class PaymentDispute extends Entity
{
    protected array $_accessible = [
        'payment_id' => true,
        'stripe_dispute_id' => true,
        'stripe_charge_id' => true,
        'stripe_payment_intent_id' => true,
        'amount' => true,
        'currency_code' => true,
        'reason' => true,
        'status' => true,
        'evidence_due_by' => true,
        'opened_at' => true,
        'closed_at' => true,
        'raw_payload' => true,
        'payment' => true,
    ];
}
