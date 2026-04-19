<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class PaymentWebhookIncident extends Entity
{
    protected array $_accessible = [
        'event_type' => true,
        'session_id' => true,
        'payment_id' => true,
        'booking_id' => true,
        'reason_code' => true,
        'severity' => true,
        'status' => true,
        'context_json' => true,
        'payload_hash' => true,
        'notes' => true,
        'created_at' => true,
        'updated_at' => true,
        'resolved_at' => true,
        'resolved_by_admin_id' => true,
        'payment' => true,
        'booking' => true,
        'resolved_by_admin' => true,
    ];
}
