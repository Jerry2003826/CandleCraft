<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\StripeWebhookEventsTable;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;

class StripeWebhookEventsTableTest extends TestCase
{
    protected array $fixtures = [
        'app.StripeWebhookEvents',
    ];

    private StripeWebhookEventsTable $StripeWebhookEvents;

    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('StripeWebhookEvents') ? [] : ['className' => StripeWebhookEventsTable::class];
        $this->StripeWebhookEvents = $this->getTableLocator()->get('StripeWebhookEvents', $config);
    }

    public function testFindOperationalProcessingExcludesSuspiciousRows(): void
    {
        $now = DateTime::now();

        $this->saveWebhookEvent([
            'event_id' => 'evt_processing_clean',
            'event_type' => 'checkout.session.completed',
            'session_id' => 'cs_processing_clean',
            'payload_hash' => hash('sha256', 'evt_processing_clean'),
            'processing_status' => 'processing',
            'suspicious_state' => 'clean',
            'first_seen_at' => $now,
            'processing_started_at' => $now,
            'last_seen_at' => $now,
        ]);

        $this->saveWebhookEvent([
            'event_id' => 'evt_processing_suspicious',
            'event_type' => 'checkout.session.completed',
            'session_id' => 'cs_processing_suspicious',
            'payload_hash' => hash('sha256', 'evt_processing_suspicious'),
            'processing_status' => 'processing',
            'suspicious_state' => 'suspicious',
            'suspicious_reason_code' => 'event_id_business_key_mismatch',
            'suspicious_seen_at' => $now,
            'first_seen_at' => $now,
            'processing_started_at' => $now,
            'last_seen_at' => $now,
        ]);

        $this->saveWebhookEvent([
            'event_id' => 'evt_legacy_suspicious',
            'event_type' => 'checkout.session.completed',
            'session_id' => 'cs_legacy_suspicious',
            'payload_hash' => hash('sha256', 'evt_legacy_suspicious'),
            'processing_status' => 'suspicious',
            'suspicious_state' => 'suspicious',
            'suspicious_reason_code' => 'legacy_suspicious_state',
            'suspicious_seen_at' => $now,
            'first_seen_at' => $now,
            'processing_started_at' => $now,
            'last_seen_at' => $now,
        ]);

        $eventIds = $this->StripeWebhookEvents->find('operationalProcessing')
            ->all()
            ->extract('event_id')
            ->toList();

        $this->assertSame(['evt_processing_clean'], $eventIds);
    }

    public function testFindSuspiciousAuditIncludesLegacyAndModernSuspiciousRows(): void
    {
        $older = DateTime::now()->subMinutes(10);
        $newer = DateTime::now()->subMinutes(2);

        $this->saveWebhookEvent([
            'event_id' => 'evt_modern_suspicious',
            'event_type' => 'checkout.session.completed',
            'session_id' => 'cs_modern_suspicious',
            'payload_hash' => hash('sha256', 'evt_modern_suspicious'),
            'processing_status' => 'processing',
            'suspicious_state' => 'suspicious',
            'suspicious_reason_code' => 'event_id_business_key_mismatch',
            'suspicious_seen_at' => $newer,
            'first_seen_at' => $older,
            'processing_started_at' => $older,
            'last_seen_at' => $newer,
        ]);

        $this->saveWebhookEvent([
            'event_id' => 'evt_legacy_suspicious_audit',
            'event_type' => 'checkout.session.completed',
            'session_id' => 'cs_legacy_suspicious_audit',
            'payload_hash' => hash('sha256', 'evt_legacy_suspicious_audit'),
            'processing_status' => 'suspicious',
            'suspicious_state' => 'suspicious',
            'suspicious_reason_code' => 'legacy_suspicious_state',
            'suspicious_seen_at' => $older,
            'first_seen_at' => $older,
            'processing_started_at' => $older,
            'last_seen_at' => $older,
        ]);

        $eventIds = $this->StripeWebhookEvents->find('suspiciousAudit')
            ->all()
            ->extract('event_id')
            ->toList();

        $this->assertSame([
            'evt_modern_suspicious',
            'evt_legacy_suspicious_audit',
        ], $eventIds);
    }

    public function testSuspiciousStateCannotBeSavedAsEmptyString(): void
    {
        $now = DateTime::now();

        $event = $this->StripeWebhookEvents->newEntity([
            'event_id' => 'evt_invalid_suspicious_state',
            'event_type' => 'checkout.session.completed',
            'session_id' => 'cs_invalid_suspicious_state',
            'payload_hash' => hash('sha256', 'evt_invalid_suspicious_state'),
            'processing_status' => 'processing',
            'suspicious_state' => '',
            'first_seen_at' => $now,
            'processing_started_at' => $now,
            'last_seen_at' => $now,
        ]);

        $this->assertNotEmpty($event->getErrors()['suspicious_state'] ?? []);
    }

    private function saveWebhookEvent(array $data): void
    {
        $entity = $this->StripeWebhookEvents->newEntity($data);
        $this->StripeWebhookEvents->saveOrFail($entity);
    }
}
