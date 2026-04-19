<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class HardenStripeWebhookEventsLedger extends BaseMigration
{
    public function up(): void
    {
        if (!$this->hasTable('stripe_webhook_events')) {
            return;
        }

        $table = $this->table('stripe_webhook_events');

        if ($table->hasColumn('id') && !$table->hasColumn('webhook_event_id')) {
            $table->renameColumn('id', 'webhook_event_id');
        }

        if (!$table->hasColumn('business_event_key')) {
            $table->addColumn('business_event_key', 'string', [
                'limit' => 255,
                'null' => true,
                'after' => 'session_id',
            ]);
        }

        if (!$table->hasColumn('processing_started_at')) {
            $table->addColumn('processing_started_at', 'datetime', [
                'null' => true,
                'after' => 'first_seen_at',
            ]);
        }

        if (!$table->hasIndex(['business_event_key'])) {
            $table->addIndex(['business_event_key'], [
                'name' => 'uk_stripe_webhook_events_business_event_key',
                'unique' => true,
            ]);
        }

        $table->update();

        $adapter = $this->getAdapter()->getAdapterType();
        if ($adapter === 'sqlite') {
            $this->execute(
                "UPDATE stripe_webhook_events
                 SET business_event_key = event_type || ':' || session_id
                 WHERE business_event_key IS NULL
                   AND session_id IS NOT NULL
                   AND session_id <> ''"
            );
        } else {
            $this->execute(
                "UPDATE stripe_webhook_events
                 SET business_event_key = CONCAT(event_type, ':', session_id)
                 WHERE business_event_key IS NULL
                   AND session_id IS NOT NULL
                   AND session_id <> ''"
            );
        }

        $this->execute(
            "UPDATE stripe_webhook_events
             SET processing_started_at = COALESCE(processing_started_at, first_seen_at, last_seen_at)
             WHERE processing_started_at IS NULL"
        );
    }

    public function down(): void
    {
        if (!$this->hasTable('stripe_webhook_events')) {
            return;
        }

        $table = $this->table('stripe_webhook_events');

        if ($table->hasIndex(['business_event_key'])) {
            $table->removeIndexByName('uk_stripe_webhook_events_business_event_key');
        }

        if ($table->hasColumn('business_event_key')) {
            $table->removeColumn('business_event_key');
        }

        if ($table->hasColumn('processing_started_at')) {
            $table->removeColumn('processing_started_at');
        }

        $table->update();
    }
}
