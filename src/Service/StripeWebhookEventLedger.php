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
    public const RESULT_CLAIMED = 'claimed';
    public const RESULT_DUPLICATE = 'duplicate';
    public const RESULT_IN_PROGRESS = 'in_progress';

    private object $eventsTable;

    public function __construct(?LocatorInterface $tableLocator = null)
    {
        $locator = $tableLocator ?? FactoryLocator::get('Table');
        $this->eventsTable = $locator->get('StripeWebhookEvents');
    }

    public function beginProcessing(string $eventId, string $eventType, string $sessionId, string $payload): string
    {
        if ($eventId === '') {
            return self::RESULT_CLAIMED;
        }

        $now = DateTime::now();
        $payloadHash = hash('sha256', $payload);
        $businessEventKey = $this->buildBusinessEventKey($eventType, $sessionId);

        $event = $this->findByEventId($eventId);
        if ($event !== null) {
            return $this->handleExistingEvent($event, $eventType, $sessionId, $businessEventKey, $payloadHash, $now);
        }

        if ($businessEventKey !== null) {
            $businessEvent = $this->findByBusinessEventKey($businessEventKey);
            if ($businessEvent !== null) {
                return $this->handleBusinessEventCollision(
                    $businessEvent,
                    $eventId,
                    $eventType,
                    $sessionId,
                    $businessEventKey,
                    $payloadHash,
                    $now
                );
            }
        }

        try {
            $event = $this->eventsTable->newEntity([
                'event_id' => $eventId,
                'event_type' => $eventType,
                'session_id' => $sessionId,
                'business_event_key' => $businessEventKey,
                'payload_hash' => $payloadHash,
                'processing_status' => 'processing',
                'first_seen_at' => $now,
                'processing_started_at' => $now,
                'last_seen_at' => $now,
            ]);
            $this->eventsTable->saveOrFail($event);

            return self::RESULT_CLAIMED;
        } catch (Throwable $exception) {
            $event = $this->findByEventId($eventId);
            if ($event === null) {
                if ($businessEventKey !== null) {
                    $event = $this->findByBusinessEventKey($businessEventKey);
                }
                if ($event === null) {
                    throw $exception;
                }
            }
        }

        if ((string)$event->event_id === $eventId) {
            return $this->handleExistingEvent($event, $eventType, $sessionId, $businessEventKey, $payloadHash, $now);
        }

        return $this->handleBusinessEventCollision(
            $event,
            $eventId,
            $eventType,
            $sessionId,
            $businessEventKey,
            $payloadHash,
            $now
        );
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

        $businessEventKey = $this->buildBusinessEventKey($eventType, $sessionId);
        $matchedByEventId = true;
        $event = $this->findByEventId($eventId);
        if ($event === null && $businessEventKey !== null) {
            $event = $this->findByBusinessEventKey($businessEventKey);
            $matchedByEventId = false;
        }
        $now = DateTime::now();

        if ($event === null) {
            $event = $this->eventsTable->newEmptyEntity();
            $event->event_id = $eventId;
            $event->first_seen_at = $now;
            $event->processing_started_at = $now;
            $matchedByEventId = true;
        }

        $event->event_type = $eventType;
        $event->session_id = $sessionId;
        $event->business_event_key = $businessEventKey;
        if ($matchedByEventId) {
            $this->preservePayloadHash($event, hash('sha256', $payload));
        } else {
            $this->logBusinessEventReplay($event, $eventId, 'status_update');
        }
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
        $businessEventKey = $this->buildBusinessEventKey($eventType, $sessionId);
        if ($businessEventKey === null) {
            return null;
        }

        return $this->eventsTable->find()
            ->where([
                'StripeWebhookEvents.business_event_key' => $businessEventKey,
                'StripeWebhookEvents.processing_status IN' => ['processed', 'ignored'],
            ])
            ->orderByDesc('StripeWebhookEvents.last_seen_at')
            ->first();
    }

    private function handleExistingEvent(
        object $event,
        string $eventType,
        string $sessionId,
        ?string $businessEventKey,
        string $payloadHash,
        DateTime $now,
    ): string {
        $status = (string)$event->processing_status;

        if (
            $businessEventKey !== null &&
            (string)($event->business_event_key ?? '') !== '' &&
            (string)$event->business_event_key !== $businessEventKey
        ) {
            Log::warning('Stripe webhook event_id matched a different business event key.', [
                'event_id' => (string)$event->event_id,
            ]);

            $this->touchExistingEvent($event, $now, $payloadHash);

            return self::RESULT_IN_PROGRESS;
        }

        if ($status === 'processing') {
            $cutoff = $now->subMinutes(self::PROCESSING_TIMEOUT_MINUTES);
            if ($event->processing_started_at !== null && $event->processing_started_at <= $cutoff) {
                return $this->claimExistingEvent($event, $now, $payloadHash, true);
            }

            $this->touchExistingEvent($event, $now, $payloadHash);

            return self::RESULT_IN_PROGRESS;
        }

        if ($status === 'failed') {
            $processedBusinessDuplicate = $this->findProcessedBusinessDuplicate($eventType, $sessionId);
            if (
                $processedBusinessDuplicate !== null &&
                (string)$processedBusinessDuplicate->event_id !== (string)$event->event_id
            ) {
                $this->touchExistingEvent($event, $now, $payloadHash);

                return self::RESULT_DUPLICATE;
            }

            return $this->claimExistingEvent($event, $now, $payloadHash, false);
        }

        $this->touchExistingEvent($event, $now, $payloadHash);

        return self::RESULT_DUPLICATE;
    }

    private function claimExistingEvent(
        object $event,
        DateTime $now,
        string $payloadHash,
        bool $onlyIfStaleProcessing,
    ): string {
        $conditions = ['event_id' => (string)$event->event_id];
        if ($onlyIfStaleProcessing) {
            $conditions['processing_status'] = 'processing';
            $conditions['processing_started_at <='] = $now->subMinutes(self::PROCESSING_TIMEOUT_MINUTES);
        } else {
            $conditions['processing_status'] = 'failed';
        }

        $updated = $this->eventsTable->updateAll([
            'processing_status' => 'processing',
            'processing_started_at' => $now,
            'last_seen_at' => $now,
        ], $conditions);

        if ($updated > 0) {
            $this->logPayloadHashMismatch($event, $payloadHash, 'retry');

            return self::RESULT_CLAIMED;
        }

        $reloaded = $this->findByEventId((string)$event->event_id);
        if ($reloaded !== null) {
            $this->touchExistingEvent($reloaded, $now, $payloadHash);
        }

        return self::RESULT_IN_PROGRESS;
    }

    private function touchExistingEvent(object $event, DateTime $now, string $payloadHash): void
    {
        $this->touchExistingEventInternal($event, $now, $payloadHash, 'duplicate', true);
    }

    private function touchBusinessDuplicateEvent(
        object $event,
        string $incomingEventId,
        DateTime $now,
        string $payloadHash,
        string $context,
    ): void {
        $this->logBusinessEventReplay($event, $incomingEventId, $context);
        $this->touchExistingEventInternal($event, $now, $payloadHash, $context, false);
    }

    private function touchExistingEventInternal(
        object $event,
        DateTime $now,
        string $payloadHash,
        string $context,
        bool $logPayloadMismatch,
    ): void {
        if ($logPayloadMismatch) {
            $this->logPayloadHashMismatch($event, $payloadHash, $context);
        }

        $event->last_seen_at = $now;
        $this->eventsTable->saveOrFail($event);
    }

    private function handleBusinessEventCollision(
        object $event,
        string $incomingEventId,
        string $eventType,
        string $sessionId,
        ?string $businessEventKey,
        string $payloadHash,
        DateTime $now,
    ): string {
        $status = (string)$event->processing_status;

        if ($status === 'processing') {
            $cutoff = $now->subMinutes(self::PROCESSING_TIMEOUT_MINUTES);
            if ($event->processing_started_at !== null && $event->processing_started_at <= $cutoff) {
                return $this->claimBusinessEvent($event, $incomingEventId, $payloadHash, $now);
            }

            $this->touchBusinessDuplicateEvent($event, $incomingEventId, $now, $payloadHash, 'business_duplicate');

            return self::RESULT_IN_PROGRESS;
        }

        if ($status === 'failed') {
            return $this->claimBusinessEvent($event, $incomingEventId, $payloadHash, $now);
        }

        $this->touchBusinessDuplicateEvent($event, $incomingEventId, $now, $payloadHash, 'business_duplicate');

        return self::RESULT_DUPLICATE;
    }

    private function claimBusinessEvent(
        object $event,
        string $incomingEventId,
        string $payloadHash,
        DateTime $now,
    ): string {
        $conditions = ['event_id' => (string)$event->event_id];
        if ((string)$event->processing_status === 'processing') {
            $conditions['processing_status'] = 'processing';
            $conditions['processing_started_at <='] = $now->subMinutes(self::PROCESSING_TIMEOUT_MINUTES);
        } else {
            $conditions['processing_status'] = 'failed';
        }

        $updated = $this->eventsTable->updateAll([
            'processing_status' => 'processing',
            'processing_started_at' => $now,
            'last_seen_at' => $now,
        ], $conditions);

        if ($updated > 0) {
            $this->logBusinessEventReplay($event, $incomingEventId, 'business_retry');

            return self::RESULT_CLAIMED;
        }

        $reloaded = $this->findByBusinessEventKey((string)$event->business_event_key);
        if ($reloaded !== null) {
            $this->touchBusinessDuplicateEvent($reloaded, $incomingEventId, $now, $payloadHash, 'business_retry');
        }

        return self::RESULT_IN_PROGRESS;
    }

    private function findByBusinessEventKey(string $businessEventKey): ?object
    {
        return $this->eventsTable->find()
            ->where(['StripeWebhookEvents.business_event_key' => $businessEventKey])
            ->first();
    }

    private function buildBusinessEventKey(string $eventType, string $sessionId): ?string
    {
        if ($sessionId === '') {
            return null;
        }

        return $eventType . ':' . $sessionId;
    }

    private function preservePayloadHash(object $event, string $payloadHash): void
    {
        $existingHash = (string)($event->payload_hash ?? '');
        if ($existingHash === '') {
            $event->payload_hash = $payloadHash;

            return;
        }

        $this->logPayloadHashMismatch($event, $payloadHash, 'status_update');
    }

    private function logPayloadHashMismatch(object $event, string $payloadHash, string $context): void
    {
        if ((string)($event->payload_hash ?? '') === '' || (string)$event->payload_hash === $payloadHash) {
            return;
        }

        Log::warning('Stripe webhook payload hash mismatch.', [
            'event_id' => (string)$event->event_id,
            'context' => $context,
        ]);
    }

    private function logBusinessEventReplay(object $event, string $incomingEventId, string $context): void
    {
        if ($incomingEventId === '' || (string)$event->event_id === $incomingEventId) {
            return;
        }

        Log::info('Stripe webhook business duplicate reused an existing ledger row.', [
            'canonical_event_id' => (string)$event->event_id,
            'incoming_event_id' => $incomingEventId,
            'business_event_key' => (string)($event->business_event_key ?? ''),
            'context' => $context,
        ]);
    }
}
