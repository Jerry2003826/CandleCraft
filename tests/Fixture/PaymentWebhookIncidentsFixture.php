<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class PaymentWebhookIncidentsFixture extends TestFixture
{
    protected array $fields = [
        'incident_id' => ['type' => 'integer', 'length' => 11, 'null' => false, 'autoIncrement' => true],
        'event_id' => ['type' => 'string', 'length' => 100, 'null' => true],
        'event_type' => ['type' => 'string', 'length' => 100, 'null' => false],
        'session_id' => ['type' => 'string', 'length' => 100, 'null' => false],
        'payment_id' => ['type' => 'integer', 'length' => 11, 'null' => true],
        'booking_id' => ['type' => 'integer', 'length' => 11, 'null' => true],
        'reason_code' => ['type' => 'string', 'length' => 100, 'null' => false],
        'severity' => ['type' => 'string', 'length' => 20, 'null' => false, 'default' => 'warning'],
        'status' => ['type' => 'string', 'length' => 20, 'null' => false, 'default' => 'open'],
        'context_json' => ['type' => 'text', 'null' => true],
        'payload_hash' => ['type' => 'string', 'length' => 64, 'null' => false],
        'notes' => ['type' => 'text', 'null' => true],
        'created_at' => ['type' => 'datetime', 'null' => false],
        'updated_at' => ['type' => 'datetime', 'null' => false],
        'resolved_at' => ['type' => 'datetime', 'null' => true],
        'resolved_by_admin_id' => ['type' => 'integer', 'length' => 11, 'null' => true],
        '_indexes' => [
            'payment_webhook_incidents_event_id_idx' => ['type' => 'index', 'columns' => ['event_id']],
            'payment_webhook_incidents_status_idx' => ['type' => 'index', 'columns' => ['status']],
        ],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['incident_id']],
        ],
    ];

    public function init(): void
    {
        $this->records = [];

        parent::init();
    }
}
