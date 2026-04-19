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

        $table->update();

        $this->backfillBusinessEventKeys();
        $this->backfillProcessingStartedAt();
        $this->detachDuplicateBusinessEventKeys();

        $table = $this->table('stripe_webhook_events');
        if ($table->hasColumn('processing_started_at')) {
            $table->changeColumn('processing_started_at', 'datetime', [
                'null' => false,
            ]);
        }
        if (!$table->hasIndex(['business_event_key'])) {
            $table->addIndex(['business_event_key'], [
                'name' => 'uk_stripe_webhook_events_business_event_key',
                'unique' => true,
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

    private function backfillBusinessEventKeys(): void
    {
        $adapter = $this->getAdapter()->getAdapterType();
        if ($adapter === 'sqlite') {
            $this->execute(
                "UPDATE stripe_webhook_events
                 SET business_event_key = event_type || ':' || session_id
                 WHERE business_event_key IS NULL
                   AND session_id IS NOT NULL
                   AND session_id <> ''"
            );

            return;
        }

        $this->execute(
            "UPDATE stripe_webhook_events
             SET business_event_key = CONCAT(event_type, ':', session_id)
             WHERE business_event_key IS NULL
               AND session_id IS NOT NULL
               AND session_id <> ''"
        );
    }

    private function backfillProcessingStartedAt(): void
    {
        $fallbackTimestamp = $this->quoteLiteral(date('Y-m-d H:i:s'));
        $this->execute(
            sprintf(
                "UPDATE stripe_webhook_events
                 SET processing_started_at = COALESCE(processing_started_at, first_seen_at, last_seen_at, %s)
                 WHERE processing_started_at IS NULL",
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

        $primaryKey = $this->primaryKeyColumn();
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

    private function primaryKeyColumn(): string
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
