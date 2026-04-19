<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Datasource\FactoryLocator;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorInterface;

class StripeWebhookEventLedger
{
    private object $eventsTable;

    public function __construct(?LocatorInterface $tableLocator = null)
    {
        $locator = $tableLocator ?? FactoryLocator::get('Table');
        $this->eventsTable = $locator->get('StripeWebhookEvents');
    }

    public function beginProcessing(string $eventId, string $eventType, string $sessionId, string $payload): bool
    {
        if ($eventId === '') {
            return true;
        }

        $event = $this->findByEventId($eventId);
        $now = DateTime::now();
        $payloadHash = hash('sha256', $payload);

        if ($event === null) {
            $event = $this->eventsTable->newEntity([
                'event_id' => $eventId,
                'event_type' => $eventType,
                'session_id' => $sessionId,
                'payload_hash' => $payloadHash,
                'processing_status' => 'processing',
                'first_seen_at' => $now,
                'last_seen_at' => $now,
            ]);
            $this->eventsTable->saveOrFail($event);

            return true;
        }

        $event->event_type = $eventType;
        $event->session_id = $sessionId;
        $event->payload_hash = $payloadHash;
        $event->last_seen_at = $now;

        if (in_array((string)$event->processing_status, ['processed', 'ignored', 'processing'], true)) {
            $this->eventsTable->saveOrFail($event);

            return false;
        }

        $event->processing_status = 'processing';
        $this->eventsTable->saveOrFail($event);

        return true;
    }

    public function markProcessed(string $eventId, string $eventType, string $sessionId, string $payload): void
    {
        $this->updateStatus($eventId, $eventType, $sessionId, $payload, 'processed');
    }

    public function markIgnored(string $eventId, string $eventType, string $sessionId, string $payload): void
    {
        $this->updateStatus($eventId, $eventType, $sessionId, $payload, 'ignored');
    }

    public function markFailed(string $eventId, string $eventType, string $sessionId, string $payload): void
    {
        $this->updateStatus($eventId, $eventType, $sessionId, $payload, 'failed');
    }

    private function updateStatus(
        string $eventId,
        string $eventType,
        string $sessionId,
        string $payload,
        string $status,
    ): void {
        if ($eventId === '') {
            return;
        }

        $event = $this->findByEventId($eventId);
        $now = DateTime::now();

        if ($event === null) {
            $event = $this->eventsTable->newEmptyEntity();
            $event->event_id = $eventId;
            $event->first_seen_at = $now;
        }

        $event->event_type = $eventType;
        $event->session_id = $sessionId;
        $event->payload_hash = hash('sha256', $payload);
        $event->processing_status = $status;
        $event->last_seen_at = $now;

        $this->eventsTable->saveOrFail($event);
    }

    private function findByEventId(string $eventId): ?object
    {
        return $this->eventsTable->find()
            ->where(['StripeWebhookEvents.event_id' => $eventId])
            ->first();
    }
}
