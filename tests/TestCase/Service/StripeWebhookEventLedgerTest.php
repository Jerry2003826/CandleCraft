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
        $this->saveWebhookEvent([
            'event_id' => 'evt_processing',
            'event_type' => 'checkout.session.completed',
            'session_id' => 'cs_processing',
            'payload_hash' => hash('sha256', '{"id":"evt_processing"}'),
            'processing_status' => 'processing',
            'first_seen_at' => '2026-04-20 12:00:00',
            'processing_started_at' => '2026-04-20 12:00:00',
            'last_seen_at' => '2026-04-20 12:09:30',
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
        $this->assertSame('2026-04-20 12:00:00', $event->processing_started_at->format('Y-m-d H:i:s'));
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
        $this->saveWebhookEvent([
            'event_id' => 'evt_processed',
            'event_type' => 'checkout.session.completed',
            'session_id' => 'cs_processed',
            'payload_hash' => $originalHash,
            'processing_status' => 'processed',
            'first_seen_at' => '2026-04-20 12:00:00',
            'processing_started_at' => '2026-04-20 12:00:00',
            'last_seen_at' => '2026-04-20 12:00:00',
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
        $this->saveWebhookEvent([
            'event_id' => 'evt_original',
            'event_type' => 'checkout.session.completed',
            'session_id' => 'cs_same_business_event',
            'payload_hash' => hash('sha256', '{"id":"evt_original"}'),
            'processing_status' => 'processed',
            'first_seen_at' => '2026-04-20 12:00:00',
            'processing_started_at' => '2026-04-20 12:00:00',
            'last_seen_at' => '2026-04-20 12:00:00',
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
        $this->saveWebhookEvent([
            'event_id' => 'evt_business_processing',
            'event_type' => 'checkout.session.completed',
            'session_id' => 'cs_business_processing',
            'payload_hash' => hash('sha256', '{"id":"evt_business_processing"}'),
            'processing_status' => 'processing',
            'first_seen_at' => '2026-04-20 12:00:00',
            'processing_started_at' => '2026-04-20 12:00:00',
            'last_seen_at' => '2026-04-20 12:05:00',
        ]);

        $claimed = $this->ledger->beginProcessing(
            'evt_business_processing_duplicate',
            'checkout.session.completed',
            'cs_business_processing',
            '{"id":"evt_business_processing_duplicate"}'
        );

        $this->assertSame(StripeWebhookEventLedger::RESULT_IN_PROGRESS, $claimed);
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
