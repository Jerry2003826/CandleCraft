<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateStripeWebhookEvents extends BaseMigration
{
    public function change(): void
    {
        if ($this->hasTable('stripe_webhook_events')) {
            return;
        }

        $this->table('stripe_webhook_events', [
            'id' => 'webhook_event_id',
        ])
            ->addColumn('event_id', 'string', [
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('event_type', 'string', [
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('session_id', 'string', [
                'limit' => 100,
                'null' => true,
            ])
            ->addColumn('payload_hash', 'string', [
                'limit' => 64,
                'null' => false,
            ])
            ->addColumn('processing_status', 'string', [
                'limit' => 20,
                'null' => false,
                'default' => 'processing',
            ])
            ->addColumn('first_seen_at', 'datetime', [
                'null' => false,
            ])
            ->addColumn('last_seen_at', 'datetime', [
                'null' => false,
            ])
            ->addIndex(['event_id'], [
                'name' => 'uk_stripe_webhook_events_event_id',
                'unique' => true,
            ])
            ->addIndex(['processing_status'], [
                'name' => 'idx_stripe_webhook_events_processing_status',
            ])
            ->create();
    }
}
