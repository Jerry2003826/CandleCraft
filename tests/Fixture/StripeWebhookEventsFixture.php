<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class StripeWebhookEventsFixture extends TestFixture
{
    protected array $fields = [
        'webhook_event_id' => ['type' => 'integer', 'length' => 11, 'null' => false, 'autoIncrement' => true],
        'event_id' => ['type' => 'string', 'length' => 100, 'null' => false],
        'event_type' => ['type' => 'string', 'length' => 100, 'null' => false],
        'session_id' => ['type' => 'string', 'length' => 100, 'null' => true],
        'payload_hash' => ['type' => 'string', 'length' => 64, 'null' => false],
        'processing_status' => ['type' => 'string', 'length' => 20, 'null' => false, 'default' => 'processing'],
        'first_seen_at' => ['type' => 'datetime', 'null' => false],
        'last_seen_at' => ['type' => 'datetime', 'null' => false],
        '_indexes' => [
            'stripe_webhook_events_processing_status_idx' => ['type' => 'index', 'columns' => ['processing_status']],
        ],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['webhook_event_id']],
            'stripe_webhook_events_event_id_uk' => ['type' => 'unique', 'columns' => ['event_id']],
        ],
    ];

    public function init(): void
    {
        $this->records = [];

        parent::init();
    }
}
