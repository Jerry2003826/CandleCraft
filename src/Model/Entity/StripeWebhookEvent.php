<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class StripeWebhookEvent extends Entity
{
    protected array $_accessible = [
        'event_id' => true,
        'event_type' => true,
        'session_id' => true,
        'business_event_key' => true,
        'payload_hash' => true,
        'processing_status' => true,
        'first_seen_at' => true,
        'processing_started_at' => true,
        'last_seen_at' => true,
    ];
}
