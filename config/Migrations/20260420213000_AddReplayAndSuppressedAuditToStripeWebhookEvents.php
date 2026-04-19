<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddReplayAndSuppressedAuditToStripeWebhookEvents extends BaseMigration
{
    public function up(): void
    {
        if (!$this->hasTable('stripe_webhook_events')) {
            return;
        }

        $table = $this->table('stripe_webhook_events');

        if (!$table->hasColumn('replay_count')) {
            $table->addColumn('replay_count', 'integer', [
                'default' => 0,
                'null' => false,
                'after' => 'suspicious_count',
            ]);
        }

        if (!$table->hasColumn('last_replay_event_id')) {
            $table->addColumn('last_replay_event_id', 'string', [
                'limit' => 100,
                'null' => true,
                'after' => 'replay_count',
            ]);
        }

        if (!$table->hasColumn('last_replay_payload_hash')) {
            $table->addColumn('last_replay_payload_hash', 'string', [
                'limit' => 64,
                'null' => true,
                'after' => 'last_replay_event_id',
            ]);
        }

        if (!$table->hasColumn('last_replay_seen_at')) {
            $table->addColumn('last_replay_seen_at', 'datetime', [
                'null' => true,
                'after' => 'last_replay_payload_hash',
            ]);
        }

        if (!$table->hasColumn('last_suppressed_status_update')) {
            $table->addColumn('last_suppressed_status_update', 'string', [
                'limit' => 20,
                'null' => true,
                'after' => 'last_replay_seen_at',
            ]);
        }

        if (!$table->hasColumn('last_suppressed_status_event_id')) {
            $table->addColumn('last_suppressed_status_event_id', 'string', [
                'limit' => 100,
                'null' => true,
                'after' => 'last_suppressed_status_update',
            ]);
        }

        if (!$table->hasColumn('last_suppressed_status_payload_hash')) {
            $table->addColumn('last_suppressed_status_payload_hash', 'string', [
                'limit' => 64,
                'null' => true,
                'after' => 'last_suppressed_status_event_id',
            ]);
        }

        if (!$table->hasColumn('last_suppressed_status_seen_at')) {
            $table->addColumn('last_suppressed_status_seen_at', 'datetime', [
                'null' => true,
                'after' => 'last_suppressed_status_payload_hash',
            ]);
        }

        $table->update();
    }

    public function down(): void
    {
        if (!$this->hasTable('stripe_webhook_events')) {
            return;
        }

        $table = $this->table('stripe_webhook_events');

        foreach ([
            'last_suppressed_status_seen_at',
            'last_suppressed_status_payload_hash',
            'last_suppressed_status_event_id',
            'last_suppressed_status_update',
            'last_replay_seen_at',
            'last_replay_payload_hash',
            'last_replay_event_id',
            'replay_count',
        ] as $column) {
            if ($table->hasColumn($column)) {
                $table->removeColumn($column);
            }
        }

        $table->update();
    }
}
