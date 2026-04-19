<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\StripeWebhookEventLedger;
use Cake\Datasource\FactoryLocator;
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
        $this->ledger = new StripeWebhookEventLedger();
        $this->eventsTable = FactoryLocator::get('Table')->get('StripeWebhookEvents');
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

        $this->assertSame(StripeWebhookEventLedger::RESULT_DUPLICATE, $claimed);
        $this->assertSame(1, $this->eventsTable->find()->count());
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
