<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddSuspiciousAuditToStripeWebhookEvents extends BaseMigration
{
    public function up(): void
    {
        if (!$this->hasTable('stripe_webhook_events')) {
            return;
        }

        $table = $this->table('stripe_webhook_events');

        if (!$table->hasColumn('suspicious_state')) {
            $table->addColumn('suspicious_state', 'string', [
                'limit' => 20,
                'null' => false,
                'default' => 'clean',
                'after' => 'processing_status',
            ]);
        }

        if (!$table->hasColumn('suspicious_reason_code')) {
            $table->addColumn('suspicious_reason_code', 'string', [
                'limit' => 100,
                'null' => true,
                'after' => 'suspicious_state',
            ]);
        }

        if (!$table->hasColumn('suspicious_seen_at')) {
            $table->addColumn('suspicious_seen_at', 'datetime', [
                'null' => true,
                'after' => 'suspicious_reason_code',
            ]);
        }

        if (!$table->hasColumn('suspicious_business_event_key')) {
            $table->addColumn('suspicious_business_event_key', 'string', [
                'limit' => 255,
                'null' => true,
                'after' => 'suspicious_seen_at',
            ]);
        }

        if (!$table->hasColumn('suspicious_payload_hash')) {
            $table->addColumn('suspicious_payload_hash', 'string', [
                'limit' => 64,
                'null' => true,
                'after' => 'suspicious_business_event_key',
            ]);
        }

        if (!$table->hasColumn('suspicious_target_status')) {
            $table->addColumn('suspicious_target_status', 'string', [
                'limit' => 20,
                'null' => true,
                'after' => 'suspicious_payload_hash',
            ]);
        }

        if (!$table->hasColumn('suspicious_count')) {
            $table->addColumn('suspicious_count', 'integer', [
                'default' => 0,
                'null' => false,
                'after' => 'suspicious_target_status',
            ]);
        }

        $table->update();

        $fallbackTimestamp = $this->quoteLiteral(date('Y-m-d H:i:s'));
        $this->execute(
            sprintf(
                "UPDATE stripe_webhook_events
                 SET suspicious_state = 'suspicious',
                     suspicious_reason_code = COALESCE(suspicious_reason_code, 'legacy_suspicious_state'),
                     suspicious_seen_at = COALESCE(suspicious_seen_at, last_seen_at, %s),
                     suspicious_count = CASE
                         WHEN COALESCE(suspicious_count, 0) < 1 THEN 1
                         ELSE suspicious_count
                     END
                 WHERE processing_status = 'suspicious'",
                $fallbackTimestamp
            )
        );
    }

    public function down(): void
    {
        if (!$this->hasTable('stripe_webhook_events')) {
            return;
        }

        $table = $this->table('stripe_webhook_events');

        foreach ([
            'suspicious_count',
            'suspicious_target_status',
            'suspicious_payload_hash',
            'suspicious_business_event_key',
            'suspicious_seen_at',
            'suspicious_reason_code',
            'suspicious_state',
        ] as $column) {
            if ($table->hasColumn($column)) {
                $table->removeColumn($column);
            }
        }

        $table->update();
    }

    private function quoteLiteral(string $value): string
    {
        return "'" . str_replace("'", "''", $value) . "'";
    }
}
