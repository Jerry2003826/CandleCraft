<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\StripeWebhookEventLedger;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;

class StripeWebhookEventLedgerTest extends TestCase
{
    protected array $fixtures = [
        'app.StripeWebhookEvents',
    ];

    private StripeWebhookEventLedger $ledger;
    private object $eventsTable;

    protected function setUp(): void
    {
        parent::setUp();
        $tableLocator = $this->getTableLocator();
        $this->ledger = new StripeWebhookEventLedger($tableLocator);
        $this->eventsTable = $tableLocator->get('StripeWebhookEvents');
    }

    public function testBeginProcessingCreatesNewProcessingRecord(): void
    {
        $claimed = $this->ledger->beginProcessing(
            'evt_new',
            'checkout.session.completed',
            'cs_new',
            '{"id":"evt_new"}'
        );

        $event = $this->eventsTable->find()
            ->where(['event_id' => 'evt_new'])
            ->firstOrFail();

        $this->assertSame(StripeWebhookEventLedger::RESULT_CLAIMED, $claimed);
        $this->assertSame('processing', $event->processing_status);
        $this->assertSame('checkout.session.completed', $event->event_type);
        $this->assertSame('cs_new', $event->session_id);
        $this->assertSame('checkout.session.completed:cs_new', $event->business_event_key);
        $this->assertNotNull($event->processing_started_at);
    }

    public function testFreshProcessingEventIsReportedAsInProgress(): void
    {
        $freshTime = DateTime::now()->subMinutes(5);
        $this->saveWebhookEvent([
            'event_id' => 'evt_processing',
            'event_type' => 'checkout.session.completed',
            'session_id' => 'cs_processing',
            'payload_hash' => hash('sha256', '{"id":"evt_processing"}'),
            'processing_status' => 'processing',
            'first_seen_at' => $freshTime,
            'processing_started_at' => $freshTime,
            'last_seen_at' => $freshTime,
        ]);

        $claimed = $this->ledger->beginProcessing(
            'evt_processing',
            'checkout.session.completed',
            'cs_processing',
            '{"id":"evt_processing"}'
        );

        $event = $this->eventsTable->find()
            ->where(['event_id' => 'evt_processing'])
            ->firstOrFail();

        $this->assertSame(StripeWebhookEventLedger::RESULT_IN_PROGRESS, $claimed);
        $this->assertSame(
            $freshTime->format('Y-m-d H:i:s'),
            $event->processing_started_at->format('Y-m-d H:i:s')
        );
    }

    public function testStaleProcessingEventCanBeReclaimed(): void
    {
        $staleTime = DateTime::now()->subMinutes(15);
        $this->saveWebhookEvent([
            'event_id' => 'evt_stale_processing',
            'event_type' => 'checkout.session.completed',
            'session_id' => 'cs_stale_processing',
            'payload_hash' => hash('sha256', '{"id":"evt_stale_processing"}'),
            'processing_status' => 'processing',
            'first_seen_at' => $staleTime,
            'processing_started_at' => $staleTime,
            'last_seen_at' => $staleTime,
        ]);

        $claimed = $this->ledger->beginProcessing(
            'evt_stale_processing',
            'checkout.session.completed',
            'cs_stale_processing',
            '{"id":"evt_stale_processing"}'
        );

        $event = $this->eventsTable->find()
            ->where(['event_id' => 'evt_stale_processing'])
            ->firstOrFail();

        $this->assertSame(StripeWebhookEventLedger::RESULT_CLAIMED, $claimed);
        $this->assertSame('processing', $event->processing_status);
        $this->assertGreaterThanOrEqual(
            DateTime::now()->subMinutes(1)->getTimestamp(),
            $event->processing_started_at->getTimestamp()
        );
    }

    public function testProcessedDuplicateDoesNotOverwriteOriginalPayloadHash(): void
    {
        $originalHash = hash('sha256', '{"id":"evt_processed"}');
        $processedTime = DateTime::now()->subMinutes(5);
        $this->saveWebhookEvent([
            'event_id' => 'evt_processed',
            'event_type' => 'checkout.session.completed',
            'session_id' => 'cs_processed',
            'payload_hash' => $originalHash,
            'processing_status' => 'processed',
            'first_seen_at' => $processedTime,
            'processing_started_at' => $processedTime,
            'last_seen_at' => $processedTime,
        ]);

        $claimed = $this->ledger->beginProcessing(
            'evt_processed',
            'checkout.session.completed',
            'cs_processed',
            '{"id":"evt_processed","changed":true}'
        );

        $event = $this->eventsTable->find()
            ->where(['event_id' => 'evt_processed'])
            ->firstOrFail();

        $this->assertSame(StripeWebhookEventLedger::RESULT_DUPLICATE, $claimed);
        $this->assertSame($originalHash, $event->payload_hash);
        $this->assertSame('cs_processed', $event->session_id);
    }

    public function testDifferentEventIdForProcessedBusinessDuplicateIsIgnored(): void
    {
        $processedTime = DateTime::now()->subMinutes(5);
        $this->saveWebhookEvent([
            'event_id' => 'evt_original',
            'event_type' => 'checkout.session.completed',
            'session_id' => 'cs_same_business_event',
            'payload_hash' => hash('sha256', '{"id":"evt_original"}'),
            'processing_status' => 'processed',
            'first_seen_at' => $processedTime,
            'processing_started_at' => $processedTime,
            'last_seen_at' => $processedTime,
        ]);

        $claimed = $this->ledger->beginProcessing(
            'evt_duplicate_business_event',
            'checkout.session.completed',
            'cs_same_business_event',
            '{"id":"evt_duplicate_business_event"}'
        );

        $event = $this->eventsTable->find()
            ->where(['event_id' => 'evt_original'])
            ->firstOrFail();

        $this->assertSame(StripeWebhookEventLedger::RESULT_DUPLICATE, $claimed);
        $this->assertSame(1, $this->eventsTable->find()->count());
        $this->assertSame(1, (int)$event->replay_count);
        $this->assertSame('evt_duplicate_business_event', $event->last_replay_event_id);
        $this->assertSame(
            hash('sha256', '{"id":"evt_duplicate_business_event"}'),
            $event->last_replay_payload_hash
        );
        $this->assertNotNull($event->last_replay_seen_at);
    }

    public function testFreshProcessingBusinessDuplicateIsReportedAsInProgress(): void
    {
        $freshTime = DateTime::now()->subMinutes(5);
        $this->saveWebhookEvent([
            'event_id' => 'evt_business_processing',
            'event_type' => 'checkout.session.completed',
            'session_id' => 'cs_business_processing',
            'payload_hash' => hash('sha256', '{"id":"evt_business_processing"}'),
            'processing_status' => 'processing',
            'first_seen_at' => $freshTime,
            'processing_started_at' => $freshTime,
            'last_seen_at' => $freshTime,
        ]);

        $claimed = $this->ledger->beginProcessing(
            'evt_business_processing_duplicate',
            'checkout.session.completed',
            'cs_business_processing',
            '{"id":"evt_business_processing_duplicate"}'
        );

        $this->assertSame(StripeWebhookEventLedger::RESULT_IN_PROGRESS, $claimed);
    }

    public function testExistingNonDetachedEventDoesNotRedirectToAnotherBusinessEventRow(): void
    {
        $eventTime = DateTime::now()->subMinutes(5);

        $this->saveWebhookEvent([
            'event_id' => 'evt_original_key',
            'event_type' => 'checkout.session.completed',
            'session_id' => 'cs_original_key',
            'payload_hash' => hash('sha256', '{"id":"evt_original_key"}'),
            'processing_status' => 'processing',
            'first_seen_at' => $eventTime,
            'processing_started_at' => $eventTime,
            'last_seen_at' => $eventTime,
        ]);

        $this->saveWebhookEvent([
            'event_id' => 'evt_other_key',
            'event_type' => 'checkout.session.completed',
            'session_id' => 'cs_other_key',
            'payload_hash' => hash('sha256', '{"id":"evt_other_key"}'),
            'processing_status' => 'failed',
            'first_seen_at' => $eventTime,
            'processing_started_at' => $eventTime,
            'last_seen_at' => $eventTime,
        ]);

        $claimed = $this->ledger->beginProcessing(
            'evt_original_key',
            'checkout.session.completed',
            'cs_other_key',
            '{"id":"evt_original_key"}'
        );

        $original = $this->eventsTable->find()
            ->where(['event_id' => 'evt_original_key'])
            ->firstOrFail();
        $other = $this->eventsTable->find()
            ->where(['event_id' => 'evt_other_key'])
            ->firstOrFail();

        $this->assertSame(StripeWebhookEventLedger::RESULT_SUSPICIOUS, $claimed);
        $this->assertSame('checkout.session.completed:cs_original_key', $original->business_event_key);
        $this->assertSame('processing', $original->processing_status);
        $this->assertSame('suspicious', $original->suspicious_state);
        $this->assertSame('event_id_business_key_mismatch', $original->suspicious_reason_code);
        $this->assertSame('checkout.session.completed:cs_other_key', $original->suspicious_business_event_key);
        $this->assertSame('checkout.session.completed:cs_other_key', $other->business_event_key);
        $this->assertSame('failed', $other->processing_status);
    }

    public function testSuspiciousStaleProcessingEventRemainsTerminal(): void
    {
        $staleTime = DateTime::now()->subMinutes(15);

        $this->saveWebhookEvent([
            'event_id' => 'evt_suspicious_processing',
            'event_type' => 'checkout.session.completed',
            'session_id' => 'cs_suspicious_processing',
            'payload_hash' => hash('sha256', '{"id":"evt_suspicious_processing"}'),
            'processing_status' => 'processing',
            'suspicious_state' => 'suspicious',
            'suspicious_reason_code' => 'event_id_business_key_mismatch',
            'suspicious_seen_at' => $staleTime,
            'suspicious_count' => 1,
            'first_seen_at' => $staleTime,
            'processing_started_at' => $staleTime,
            'last_seen_at' => $staleTime,
        ]);

        $claimed = $this->ledger->beginProcessing(
            'evt_suspicious_processing',
            'checkout.session.completed',
            'cs_suspicious_processing',
            '{"id":"evt_suspicious_processing"}'
        );

        $event = $this->eventsTable->find()
            ->where(['event_id' => 'evt_suspicious_processing'])
            ->firstOrFail();

        $this->assertSame(StripeWebhookEventLedger::RESULT_SUSPICIOUS, $claimed);
        $this->assertSame('processing', $event->processing_status);
        $this->assertSame(
            $staleTime->format('Y-m-d H:i:s'),
            $event->processing_started_at->format('Y-m-d H:i:s')
        );
    }

    public function testSuspiciousBusinessCollisionDoesNotClaimCanonicalRow(): void
    {
        $staleTime = DateTime::now()->subMinutes(15);

        $this->saveWebhookEvent([
            'event_id' => 'evt_suspicious_canonical',
            'event_type' => 'checkout.session.completed',
            'session_id' => 'cs_suspicious_business',
            'payload_hash' => hash('sha256', '{"id":"evt_suspicious_canonical"}'),
            'processing_status' => 'failed',
            'suspicious_state' => 'suspicious',
            'suspicious_reason_code' => 'event_id_business_key_mismatch',
            'suspicious_seen_at' => $staleTime,
            'suspicious_count' => 1,
            'first_seen_at' => $staleTime,
            'processing_started_at' => $staleTime,
            'last_seen_at' => $staleTime,
        ]);

        $claimed = $this->ledger->beginProcessing(
            'evt_suspicious_business_retry',
            'checkout.session.completed',
            'cs_suspicious_business',
            '{"id":"evt_suspicious_business_retry"}'
        );

        $event = $this->eventsTable->find()
            ->where(['event_id' => 'evt_suspicious_canonical'])
            ->firstOrFail();

        $this->assertSame(StripeWebhookEventLedger::RESULT_SUSPICIOUS, $claimed);
        $this->assertSame('failed', $event->processing_status);
        $this->assertSame('suspicious', $event->suspicious_state);
        $this->assertSame(1, (int)$event->replay_count);
        $this->assertSame('evt_suspicious_business_retry', $event->last_replay_event_id);
        $this->assertSame(1, $this->eventsTable->find()->count());
    }

    public function testBusinessRetryClaimPreservesCanonicalEventIdAndPayloadHash(): void
    {
        $failedTime = DateTime::now()->subMinutes(15);
        $originalHash = hash('sha256', '{"id":"evt_failed"}');

        $this->saveWebhookEvent([
            'event_id' => 'evt_failed',
            'event_type' => 'checkout.session.completed',
            'session_id' => 'cs_claim_reuse',
            'payload_hash' => $originalHash,
            'processing_status' => 'failed',
            'first_seen_at' => $failedTime,
            'processing_started_at' => $failedTime,
            'last_seen_at' => $failedTime,
        ]);

        $claimed = $this->ledger->beginProcessing(
            'evt_retry',
            'checkout.session.completed',
            'cs_claim_reuse',
            '{"id":"evt_retry"}'
        );

        $event = $this->eventsTable->find()
            ->where(['event_id' => 'evt_failed'])
            ->firstOrFail();

        $this->assertSame(StripeWebhookEventLedger::RESULT_CLAIMED, $claimed);
        $this->assertSame('evt_failed', $event->event_id);
        $this->assertSame($originalHash, $event->payload_hash);
        $this->assertSame('processing', $event->processing_status);

        $this->ledger->markProcessed(
            'evt_retry',
            'checkout.session.completed',
            'cs_claim_reuse',
            '{"id":"evt_retry"}'
        );

        $updated = $this->eventsTable->find()
            ->where(['event_id' => 'evt_failed'])
            ->firstOrFail();

        $this->assertSame('processed', $updated->processing_status);
        $this->assertSame('evt_failed', $updated->event_id);
        $this->assertSame($originalHash, $updated->payload_hash);
        $this->assertSame(1, $this->eventsTable->find()->count());
    }

    public function testDetachedFailedRowRetryUsesCanonicalBusinessEventRow(): void
    {
        $failedTime = DateTime::now()->subMinutes(15);
        $canonicalHash = hash('sha256', '{"id":"evt_canonical"}');
        $detachedHash = hash('sha256', '{"id":"evt_detached"}');

        $this->saveWebhookEvent([
            'event_id' => 'evt_canonical',
            'event_type' => 'checkout.session.completed',
            'session_id' => 'cs_detached_retry',
            'payload_hash' => $canonicalHash,
            'processing_status' => 'failed',
            'first_seen_at' => $failedTime,
            'processing_started_at' => $failedTime,
            'last_seen_at' => $failedTime,
        ]);

        $this->saveWebhookEvent([
            'event_id' => 'evt_detached',
            'event_type' => 'checkout.session.completed',
            'session_id' => 'cs_detached_retry',
            'business_event_key' => null,
            'payload_hash' => $detachedHash,
            'processing_status' => 'failed',
            'first_seen_at' => $failedTime,
            'processing_started_at' => $failedTime,
            'last_seen_at' => $failedTime,
        ]);

        $claimed = $this->ledger->beginProcessing(
            'evt_detached',
            'checkout.session.completed',
            'cs_detached_retry',
            '{"id":"evt_detached"}'
        );

        $this->assertSame(StripeWebhookEventLedger::RESULT_CLAIMED, $claimed);

        $this->ledger->markProcessed(
            'evt_detached',
            'checkout.session.completed',
            'cs_detached_retry',
            '{"id":"evt_detached"}'
        );

        $canonical = $this->eventsTable->find()
            ->where(['event_id' => 'evt_canonical'])
            ->firstOrFail();
        $detached = $this->eventsTable->find()
            ->where(['event_id' => 'evt_detached'])
            ->firstOrFail();

        $this->assertSame('processed', $canonical->processing_status);
        $this->assertSame('checkout.session.completed:cs_detached_retry', $canonical->business_event_key);
        $this->assertSame($canonicalHash, $canonical->payload_hash);

        $this->assertSame('failed', $detached->processing_status);
        $this->assertNull($detached->business_event_key);
        $this->assertSame($detachedHash, $detached->payload_hash);
        $this->assertSame(2, $this->eventsTable->find()->count());
    }

    public function testDetachedStaleProcessingRowIsSuppressedWhenCanonicalBusinessEventIsProcessed(): void
    {
        $processedTime = DateTime::now()->subMinutes(5);
        $staleTime = DateTime::now()->subMinutes(15);

        $this->saveWebhookEvent([
            'event_id' => 'evt_canonical_processed',
            'event_type' => 'checkout.session.completed',
            'session_id' => 'cs_detached_stale',
            'payload_hash' => hash('sha256', '{"id":"evt_canonical_processed"}'),
            'processing_status' => 'processed',
            'first_seen_at' => $processedTime,
            'processing_started_at' => $processedTime,
            'last_seen_at' => $processedTime,
        ]);

        $this->saveWebhookEvent([
            'event_id' => 'evt_detached_stale',
            'event_type' => 'checkout.session.completed',
            'session_id' => 'cs_detached_stale',
            'business_event_key' => null,
            'payload_hash' => hash('sha256', '{"id":"evt_detached_stale"}'),
            'processing_status' => 'processing',
            'first_seen_at' => $staleTime,
            'processing_started_at' => $staleTime,
            'last_seen_at' => $staleTime,
        ]);

        $claimed = $this->ledger->beginProcessing(
            'evt_detached_stale',
            'checkout.session.completed',
            'cs_detached_stale',
            '{"id":"evt_detached_stale"}'
        );

        $canonical = $this->eventsTable->find()
            ->where(['event_id' => 'evt_canonical_processed'])
            ->firstOrFail();
        $detached = $this->eventsTable->find()
            ->where(['event_id' => 'evt_detached_stale'])
            ->firstOrFail();

        $this->assertSame(StripeWebhookEventLedger::RESULT_DUPLICATE, $claimed);
        $this->assertSame('processed', $canonical->processing_status);
        $this->assertSame('processing', $detached->processing_status);
        $this->assertNull($detached->business_event_key);
    }

    public function testStatusUpdateDoesNotRewriteExistingBusinessEventKey(): void
    {
        $eventTime = DateTime::now()->subMinutes(5);

        $this->saveWebhookEvent([
            'event_id' => 'evt_status_guard',
            'event_type' => 'checkout.session.completed',
            'session_id' => 'cs_status_guard',
            'payload_hash' => hash('sha256', '{"id":"evt_status_guard"}'),
            'processing_status' => 'failed',
            'first_seen_at' => $eventTime,
            'processing_started_at' => $eventTime,
            'last_seen_at' => $eventTime,
        ]);

        $this->ledger->markProcessed(
            'evt_status_guard',
            'checkout.session.completed',
            'cs_other_status_guard',
            '{"id":"evt_status_guard"}'
        );

        $event = $this->eventsTable->find()
            ->where(['event_id' => 'evt_status_guard'])
            ->firstOrFail();

        $this->assertSame('failed', $event->processing_status);
        $this->assertSame('suspicious', $event->suspicious_state);
        $this->assertSame('status_update_business_key_mismatch', $event->suspicious_reason_code);
        $this->assertSame('checkout.session.completed:cs_other_status_guard', $event->suspicious_business_event_key);
        $this->assertSame('processed', $event->suspicious_target_status);
        $this->assertSame('checkout.session.completed:cs_status_guard', $event->business_event_key);
    }

    public function testSuppressedStatusUpdateIsAuditedOnSuspiciousRow(): void
    {
        $eventTime = DateTime::now()->subMinutes(5);

        $this->saveWebhookEvent([
            'event_id' => 'evt_suspicious_status_update',
            'event_type' => 'checkout.session.completed',
            'session_id' => 'cs_suspicious_status_update',
            'payload_hash' => hash('sha256', '{"id":"evt_suspicious_status_update"}'),
            'processing_status' => 'processing',
            'suspicious_state' => 'suspicious',
            'suspicious_reason_code' => 'event_id_business_key_mismatch',
            'suspicious_seen_at' => $eventTime,
            'suspicious_count' => 1,
            'first_seen_at' => $eventTime,
            'processing_started_at' => $eventTime,
            'last_seen_at' => $eventTime,
        ]);

        $this->ledger->markProcessed(
            'evt_suspicious_status_update',
            'checkout.session.completed',
            'cs_suspicious_status_update',
            '{"id":"evt_suspicious_status_update","replayed":true}'
        );

        $event = $this->eventsTable->find()
            ->where(['event_id' => 'evt_suspicious_status_update'])
            ->firstOrFail();

        $this->assertSame('processing', $event->processing_status);
        $this->assertSame('suspicious', $event->suspicious_state);
        $this->assertSame('processed', $event->last_suppressed_status_update);
        $this->assertSame('evt_suspicious_status_update', $event->last_suppressed_status_event_id);
        $this->assertSame(
            hash('sha256', '{"id":"evt_suspicious_status_update","replayed":true}'),
            $event->last_suppressed_status_payload_hash
        );
        $this->assertNotNull($event->last_suppressed_status_seen_at);
    }

    public function testStatusUpdateDoesNotClearExistingBusinessEventKeyWhenIncomingSessionIsMissing(): void
    {
        $eventTime = DateTime::now()->subMinutes(5);

        $this->saveWebhookEvent([
            'event_id' => 'evt_missing_session',
            'event_type' => 'checkout.session.completed',
            'session_id' => 'cs_missing_session',
            'payload_hash' => hash('sha256', '{"id":"evt_missing_session"}'),
            'processing_status' => 'failed',
            'first_seen_at' => $eventTime,
            'processing_started_at' => $eventTime,
            'last_seen_at' => $eventTime,
        ]);

        $this->ledger->markProcessed(
            'evt_missing_session',
            'checkout.session.completed',
            '',
            '{"id":"evt_missing_session"}'
        );

        $event = $this->eventsTable->find()
            ->where(['event_id' => 'evt_missing_session'])
            ->firstOrFail();

        $this->assertSame('failed', $event->processing_status);
        $this->assertSame('suspicious', $event->suspicious_state);
        $this->assertSame('status_update_business_key_mismatch', $event->suspicious_reason_code);
        $this->assertNull($event->suspicious_business_event_key);
        $this->assertSame('processed', $event->suspicious_target_status);
        $this->assertSame('checkout.session.completed:cs_missing_session', $event->business_event_key);
    }

    private function saveWebhookEvent(array $data): void
    {
        $data += [
            'business_event_key' => isset($data['event_type'], $data['session_id']) && $data['session_id'] !== ''
                ? $data['event_type'] . ':' . $data['session_id']
                : null,
            'processing_started_at' => $data['first_seen_at'] ?? DateTime::now(),
        ];

        $this->eventsTable->saveOrFail($this->eventsTable->newEntity($data));
    }
}
