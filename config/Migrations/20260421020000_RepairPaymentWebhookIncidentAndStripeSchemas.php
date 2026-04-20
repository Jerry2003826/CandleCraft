<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class RepairPaymentWebhookIncidentAndStripeSchemas extends BaseMigration
{
    public function up(): void
    {
        $this->repairPaymentWebhookIncidents();
        $this->repairStripeWebhookEvents();
    }

    public function down(): void
    {
        // The repair migration is intentionally irreversible because it may
        // normalize legacy primary keys and detach duplicate business keys.
    }

    private function repairPaymentWebhookIncidents(): void
    {
        if (!$this->hasTable('payment_webhook_incidents')) {
            $this->createPaymentWebhookIncidentsTable();

            return;
        }

        $table = $this->table('payment_webhook_incidents');
        if ($table->hasColumn('id') && !$table->hasColumn('incident_id')) {
            $table->renameColumn('id', 'incident_id');
        }
        if (!$table->hasColumn('event_id')) {
            $table->addColumn('event_id', 'string', [
                'limit' => 100,
                'null' => true,
            ]);
        }
        $table->update();

        $table = $this->table('payment_webhook_incidents');
        if (!$table->hasIndex(['event_id'])) {
            $table->addIndex(['event_id'], [
                'name' => 'idx_payment_webhook_incidents_event_id',
            ]);
        }
        if (!$table->hasIndex(['event_id', 'reason_code', 'status'])) {
            $table->addIndex(['event_id', 'reason_code', 'status'], [
                'name' => 'uk_payment_webhook_incidents_event_reason_status',
                'unique' => true,
            ]);
        }
        $table->update();
    }

    private function createPaymentWebhookIncidentsTable(): void
    {
        $table = $this->table('payment_webhook_incidents', [
            'id' => 'incident_id',
        ]);

        $table
            ->addColumn('event_id', 'string', [
                'limit' => 100,
                'null' => true,
            ])
            ->addColumn('event_type', 'string', [
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('session_id', 'string', [
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('payment_id', 'biginteger', [
                'null' => true,
                'signed' => false,
            ])
            ->addColumn('booking_id', 'biginteger', [
                'null' => true,
                'signed' => false,
            ])
            ->addColumn('reason_code', 'string', [
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('severity', 'string', [
                'limit' => 20,
                'null' => false,
            ])
            ->addColumn('status', 'string', [
                'limit' => 20,
                'null' => false,
                'default' => 'open',
            ])
            ->addColumn('context_json', 'text', [
                'null' => true,
            ])
            ->addColumn('payload_hash', 'string', [
                'limit' => 64,
                'null' => false,
            ])
            ->addColumn('notes', 'text', [
                'null' => true,
            ])
            ->addColumn('created_at', 'datetime', [
                'null' => true,
            ])
            ->addColumn('updated_at', 'datetime', [
                'null' => true,
            ])
            ->addColumn('resolved_at', 'datetime', [
                'null' => true,
            ])
            ->addColumn('resolved_by_admin_id', 'biginteger', [
                'null' => true,
                'signed' => false,
            ])
            ->addIndex(['status'], ['name' => 'idx_payment_webhook_incidents_status'])
            ->addIndex(['event_type'], ['name' => 'idx_payment_webhook_incidents_event_type'])
            ->addIndex(['event_id'], ['name' => 'idx_payment_webhook_incidents_event_id'])
            ->addIndex(['session_id'], ['name' => 'idx_payment_webhook_incidents_session_id'])
            ->addIndex(['reason_code'], ['name' => 'idx_payment_webhook_incidents_reason_code'])
            ->addIndex(['created_at'], ['name' => 'idx_payment_webhook_incidents_created_at'])
            ->addIndex(['event_id', 'reason_code', 'status'], [
                'name' => 'uk_payment_webhook_incidents_event_reason_status',
                'unique' => true,
            ]);

        if ($this->hasTable('payments')) {
            $table->addForeignKey('payment_id', 'payments', 'payment_id', [
                'delete' => 'SET_NULL',
                'update' => 'CASCADE',
            ]);
        }
        if ($this->hasTable('bookings')) {
            $table->addForeignKey('booking_id', 'bookings', 'booking_id', [
                'delete' => 'SET_NULL',
                'update' => 'CASCADE',
            ]);
        }
        if ($this->hasTable('admins')) {
            $table->addForeignKey('resolved_by_admin_id', 'admins', 'admin_id', [
                'delete' => 'SET_NULL',
                'update' => 'CASCADE',
            ]);
        }

        $table->create();
    }

    private function repairStripeWebhookEvents(): void
    {
        if (!$this->hasTable('stripe_webhook_events')) {
            $this->createStripeWebhookEventsTable();

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
            ]);
        }
        if (!$table->hasColumn('processing_started_at')) {
            $table->addColumn('processing_started_at', 'datetime', [
                'null' => true,
            ]);
        }
        if (!$table->hasColumn('suspicious_state')) {
            $table->addColumn('suspicious_state', 'string', [
                'limit' => 20,
                'null' => false,
                'default' => 'clean',
            ]);
        }
        if (!$table->hasColumn('suspicious_reason_code')) {
            $table->addColumn('suspicious_reason_code', 'string', [
                'limit' => 100,
                'null' => true,
            ]);
        }
        if (!$table->hasColumn('suspicious_seen_at')) {
            $table->addColumn('suspicious_seen_at', 'datetime', [
                'null' => true,
            ]);
        }
        if (!$table->hasColumn('suspicious_business_event_key')) {
            $table->addColumn('suspicious_business_event_key', 'string', [
                'limit' => 255,
                'null' => true,
            ]);
        }
        if (!$table->hasColumn('suspicious_payload_hash')) {
            $table->addColumn('suspicious_payload_hash', 'string', [
                'limit' => 64,
                'null' => true,
            ]);
        }
        if (!$table->hasColumn('suspicious_target_status')) {
            $table->addColumn('suspicious_target_status', 'string', [
                'limit' => 20,
                'null' => true,
            ]);
        }
        if (!$table->hasColumn('suspicious_count')) {
            $table->addColumn('suspicious_count', 'integer', [
                'default' => 0,
                'null' => false,
            ]);
        }
        if (!$table->hasColumn('replay_count')) {
            $table->addColumn('replay_count', 'integer', [
                'default' => 0,
                'null' => false,
            ]);
        }
        if (!$table->hasColumn('last_replay_event_id')) {
            $table->addColumn('last_replay_event_id', 'string', [
                'limit' => 100,
                'null' => true,
            ]);
        }
        if (!$table->hasColumn('last_replay_payload_hash')) {
            $table->addColumn('last_replay_payload_hash', 'string', [
                'limit' => 64,
                'null' => true,
            ]);
        }
        if (!$table->hasColumn('last_replay_seen_at')) {
            $table->addColumn('last_replay_seen_at', 'datetime', [
                'null' => true,
            ]);
        }
        if (!$table->hasColumn('last_suppressed_status_update')) {
            $table->addColumn('last_suppressed_status_update', 'string', [
                'limit' => 20,
                'null' => true,
            ]);
        }
        if (!$table->hasColumn('last_suppressed_status_event_id')) {
            $table->addColumn('last_suppressed_status_event_id', 'string', [
                'limit' => 100,
                'null' => true,
            ]);
        }
        if (!$table->hasColumn('last_suppressed_status_payload_hash')) {
            $table->addColumn('last_suppressed_status_payload_hash', 'string', [
                'limit' => 64,
                'null' => true,
            ]);
        }
        if (!$table->hasColumn('last_suppressed_status_seen_at')) {
            $table->addColumn('last_suppressed_status_seen_at', 'datetime', [
                'null' => true,
            ]);
        }
        $table->update();

        $this->backfillStripeWebhookEventColumns();
        $this->detachDuplicateBusinessEventKeys();

        $table = $this->table('stripe_webhook_events');
        if ($table->hasColumn('processing_started_at')) {
            $table->changeColumn('processing_started_at', 'datetime', [
                'null' => false,
            ]);
        }
        if (!$table->hasIndex(['event_id'])) {
            $table->addIndex(['event_id'], [
                'name' => 'uk_stripe_webhook_events_event_id',
                'unique' => true,
            ]);
        }
        if (!$table->hasIndex(['processing_status'])) {
            $table->addIndex(['processing_status'], [
                'name' => 'idx_stripe_webhook_events_processing_status',
            ]);
        }
        if (!$table->hasIndex(['business_event_key'])) {
            $table->addIndex(['business_event_key'], [
                'name' => 'uk_stripe_webhook_events_business_event_key',
                'unique' => true,
            ]);
        }
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

    private function createStripeWebhookEventsTable(): void
    {
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
            ->addColumn('business_event_key', 'string', [
                'limit' => 255,
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
            ->addColumn('suspicious_state', 'string', [
                'limit' => 20,
                'null' => false,
                'default' => 'clean',
            ])
            ->addColumn('suspicious_reason_code', 'string', [
                'limit' => 100,
                'null' => true,
            ])
            ->addColumn('suspicious_seen_at', 'datetime', [
                'null' => true,
            ])
            ->addColumn('suspicious_business_event_key', 'string', [
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('suspicious_payload_hash', 'string', [
                'limit' => 64,
                'null' => true,
            ])
            ->addColumn('suspicious_target_status', 'string', [
                'limit' => 20,
                'null' => true,
            ])
            ->addColumn('suspicious_count', 'integer', [
                'default' => 0,
                'null' => false,
            ])
            ->addColumn('replay_count', 'integer', [
                'default' => 0,
                'null' => false,
            ])
            ->addColumn('last_replay_event_id', 'string', [
                'limit' => 100,
                'null' => true,
            ])
            ->addColumn('last_replay_payload_hash', 'string', [
                'limit' => 64,
                'null' => true,
            ])
            ->addColumn('last_replay_seen_at', 'datetime', [
                'null' => true,
            ])
            ->addColumn('last_suppressed_status_update', 'string', [
                'limit' => 20,
                'null' => true,
            ])
            ->addColumn('last_suppressed_status_event_id', 'string', [
                'limit' => 100,
                'null' => true,
            ])
            ->addColumn('last_suppressed_status_payload_hash', 'string', [
                'limit' => 64,
                'null' => true,
            ])
            ->addColumn('last_suppressed_status_seen_at', 'datetime', [
                'null' => true,
            ])
            ->addColumn('first_seen_at', 'datetime', [
                'null' => false,
            ])
            ->addColumn('processing_started_at', 'datetime', [
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
            ->addIndex(['business_event_key'], [
                'name' => 'uk_stripe_webhook_events_business_event_key',
                'unique' => true,
            ])
            ->addIndex(['suspicious_state', 'suspicious_seen_at'], [
                'name' => 'idx_stripe_webhook_events_suspicious_audit',
            ])
            ->addIndex(['suspicious_reason_code', 'suspicious_seen_at'], [
                'name' => 'idx_stripe_webhook_events_suspicious_reason',
            ])
            ->create();
    }

    private function backfillStripeWebhookEventColumns(): void
    {
        $fallbackTimestamp = $this->quoteLiteral(date('Y-m-d H:i:s'));

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
            sprintf(
                "UPDATE stripe_webhook_events
                 SET processing_started_at = COALESCE(processing_started_at, first_seen_at, last_seen_at, %s)
                 WHERE processing_started_at IS NULL",
                $fallbackTimestamp
            )
        );

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

    private function detachDuplicateBusinessEventKeys(): void
    {
        $duplicates = $this->fetchAll(
            "SELECT business_event_key
             FROM stripe_webhook_events
             WHERE business_event_key IS NOT NULL
               AND business_event_key <> ''
             GROUP BY business_event_key
             HAVING COUNT(*) > 1"
        );

        if ($duplicates === []) {
            return;
        }

        $primaryKey = $this->stripeWebhookEventPrimaryKey();
        foreach ($duplicates as $duplicate) {
            $businessEventKey = (string)($duplicate['business_event_key'] ?? '');
            if ($businessEventKey === '') {
                continue;
            }

            $rows = $this->fetchAll(sprintf(
                "SELECT %s AS pk, processing_status, first_seen_at, last_seen_at
                 FROM stripe_webhook_events
                 WHERE business_event_key = %s",
                $primaryKey,
                $this->quoteLiteral($businessEventKey)
            ));

            if (count($rows) < 2) {
                continue;
            }

            usort($rows, function (array $left, array $right): int {
                $statusComparison = $this->statusPriority((string)($left['processing_status'] ?? ''))
                    <=> $this->statusPriority((string)($right['processing_status'] ?? ''));
                if ($statusComparison !== 0) {
                    return $statusComparison;
                }

                $leftSeenAt = (string)($left['last_seen_at'] ?? $left['first_seen_at'] ?? '');
                $rightSeenAt = (string)($right['last_seen_at'] ?? $right['first_seen_at'] ?? '');
                if ($leftSeenAt !== $rightSeenAt) {
                    return strcmp($rightSeenAt, $leftSeenAt);
                }

                return (int)($left['pk'] ?? 0) <=> (int)($right['pk'] ?? 0);
            });

            array_shift($rows);
            foreach ($rows as $row) {
                $this->execute(sprintf(
                    "UPDATE stripe_webhook_events
                     SET business_event_key = NULL
                     WHERE %s = %d",
                    $primaryKey,
                    (int)($row['pk'] ?? 0)
                ));
            }
        }
    }

    private function stripeWebhookEventPrimaryKey(): string
    {
        $table = $this->table('stripe_webhook_events');

        return $table->hasColumn('webhook_event_id') ? 'webhook_event_id' : 'id';
    }

    private function quoteLiteral(string $value): string
    {
        return "'" . str_replace("'", "''", $value) . "'";
    }

    private function statusPriority(string $status): int
    {
        return match ($status) {
            'processed' => 0,
            'ignored' => 1,
            'processing' => 2,
            'failed' => 3,
            default => 4,
        };
    }
}
