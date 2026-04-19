<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddStripeWebhookSuspiciousAuditIndexes extends BaseMigration
{
    public function up(): void
    {
        if (!$this->hasTable('stripe_webhook_events')) {
            return;
        }

        $table = $this->table('stripe_webhook_events');

        if (
            $table->hasColumn('suspicious_state') &&
            $table->hasColumn('suspicious_seen_at') &&
            !$table->hasIndex(['suspicious_state', 'suspicious_seen_at'])
        ) {
            $table->addIndex(['suspicious_state', 'suspicious_seen_at'], [
                'name' => 'idx_stripe_webhook_events_suspicious_audit',
            ]);
        }

        if (
            $table->hasColumn('suspicious_reason_code') &&
            $table->hasColumn('suspicious_seen_at') &&
            !$table->hasIndex(['suspicious_reason_code', 'suspicious_seen_at'])
        ) {
            $table->addIndex(['suspicious_reason_code', 'suspicious_seen_at'], [
                'name' => 'idx_stripe_webhook_events_suspicious_reason',
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

        if ($table->hasIndexByName('idx_stripe_webhook_events_suspicious_reason')) {
            $table->removeIndexByName('idx_stripe_webhook_events_suspicious_reason');
        }

        if ($table->hasIndexByName('idx_stripe_webhook_events_suspicious_audit')) {
            $table->removeIndexByName('idx_stripe_webhook_events_suspicious_audit');
        }

        $table->update();
    }
}
