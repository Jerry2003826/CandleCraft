<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Datasource\FactoryLocator;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorInterface;
use Throwable;

class StripeWebhookEventLedger
{
    private const PROCESSING_TIMEOUT_MINUTES = 10;

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

        $now = DateTime::now();
        $payloadHash = hash('sha256', $payload);

        $event = $this->findByEventId($eventId);
        if ($event !== null) {
            return $this->handleExistingEvent($event, $eventType, $sessionId, $payloadHash, $now);
        }

        $businessDuplicate = $this->findProcessedBusinessDuplicate($eventType, $sessionId);
        if ($businessDuplicate !== null) {
            $this->touchExistingEvent($businessDuplicate, $now, $payloadHash);

            return false;
        }

        try {
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
        } catch (Throwable $exception) {
            $event = $this->findByEventId($eventId);
            if ($event === null) {
                throw $exception;
            }
        }

        return $this->handleExistingEvent($event, $eventType, $sessionId, $payloadHash, $now);
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

    private function findProcessedBusinessDuplicate(string $eventType, string $sessionId): ?object
    {
        if ($sessionId === '') {
            return null;
        }

        return $this->eventsTable->find()
            ->where([
                'StripeWebhookEvents.event_type' => $eventType,
                'StripeWebhookEvents.session_id' => $sessionId,
                'StripeWebhookEvents.processing_status IN' => ['processed', 'ignored'],
            ])
            ->orderByDesc('StripeWebhookEvents.last_seen_at')
            ->first();
    }

    private function handleExistingEvent(
        object $event,
        string $eventType,
        string $sessionId,
        string $payloadHash,
        DateTime $now,
    ): bool {
        $status = (string)$event->processing_status;

        if ($status === 'processing') {
            $cutoff = $now->subMinutes(self::PROCESSING_TIMEOUT_MINUTES);
            if ($event->last_seen_at !== null && $event->last_seen_at <= $cutoff) {
                return $this->claimExistingEvent($event, $now, $payloadHash, true);
            }

            $this->touchExistingEvent($event, $now, $payloadHash);

            return false;
        }

        if ($status === 'failed') {
            return $this->claimExistingEvent($event, $now, $payloadHash, false);
        }

        $this->touchExistingEvent($event, $now, $payloadHash);

        return false;
    }

    private function claimExistingEvent(
        object $event,
        DateTime $now,
        string $payloadHash,
        bool $onlyIfStaleProcessing,
    ): bool {
        $conditions = ['event_id' => (string)$event->event_id];
        if ($onlyIfStaleProcessing) {
            $conditions['processing_status'] = 'processing';
            $conditions['last_seen_at <='] = $now->subMinutes(self::PROCESSING_TIMEOUT_MINUTES);
        } else {
            $conditions['processing_status'] = 'failed';
        }

        $updated = $this->eventsTable->updateAll([
            'processing_status' => 'processing',
            'last_seen_at' => $now,
        ], $conditions);

        if ($updated > 0) {
            if ((string)$event->payload_hash !== $payloadHash) {
                Log::warning('Stripe webhook retry payload hash mismatch while reclaiming event.', [
                    'event_id' => (string)$event->event_id,
                ]);
            }

            return true;
        }

        $reloaded = $this->findByEventId((string)$event->event_id);
        if ($reloaded !== null) {
            $this->touchExistingEvent($reloaded, $now, $payloadHash);
        }

        return false;
    }

    private function touchExistingEvent(object $event, DateTime $now, string $payloadHash): void
    {
        if ((string)$event->payload_hash !== '' && (string)$event->payload_hash !== $payloadHash) {
            Log::warning('Stripe webhook duplicate payload hash mismatch.', [
                'event_id' => (string)$event->event_id,
            ]);
        }

        $event->last_seen_at = $now;
        $this->eventsTable->saveOrFail($event);
    }
}
