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
    public const RESULT_SUSPICIOUS = 'suspicious';

    private object $eventsTable;

    /**
     * Construct.
     *
     * @param mixed $tableLocator Tablelocator.
     * @return mixed
     */
    public function __construct(?LocatorInterface $tableLocator = null)
    {
        $locator = $tableLocator ?? FactoryLocator::get('Table');
        $this->eventsTable = $locator->get('StripeWebhookEvents');
    }

    /**
     * Begin processing.
     *
     * @param mixed $eventId Eventid.
     * @param mixed $eventType Eventtype.
     * @param mixed $sessionId Sessionid.
     * @param mixed $payload Payload.
     */
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
            $businessCollision = $this->findExistingBusinessCollision($event, $businessEventKey);
            if ($businessCollision !== null) {
                return $this->handleBusinessEventCollision(
                    $businessCollision,
                    $eventId,
                    $eventType,
                    $sessionId,
                    $businessEventKey,
                    $payloadHash,
                    $now,
                );
            }

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
                    $now,
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
            $now,
        );
    }

    /**
     * Mark processed.
     *
     * @param mixed $eventId Eventid.
     * @param mixed $eventType Eventtype.
     * @param mixed $sessionId Sessionid.
     * @param mixed $payload Payload.
     */
    public function markProcessed(string $eventId, string $eventType, string $sessionId, string $payload): void
    {
        $this->updateStatus($eventId, $eventType, $sessionId, $payload, 'processed');
    }

    /**
     * Mark ignored.
     *
     * @param mixed $eventId Eventid.
     * @param mixed $eventType Eventtype.
     * @param mixed $sessionId Sessionid.
     * @param mixed $payload Payload.
     */
    public function markIgnored(string $eventId, string $eventType, string $sessionId, string $payload): void
    {
        $this->updateStatus($eventId, $eventType, $sessionId, $payload, 'ignored');
    }

    /**
     * Mark failed.
     *
     * @param mixed $eventId Eventid.
     * @param mixed $eventType Eventtype.
     * @param mixed $sessionId Sessionid.
     * @param mixed $payload Payload.
     */
    public function markFailed(string $eventId, string $eventType, string $sessionId, string $payload): void
    {
        $this->updateStatus($eventId, $eventType, $sessionId, $payload, 'failed');
    }

    /**
     * Update status.
     *
     * @param mixed $eventId Eventid.
     * @param mixed $eventType Eventtype.
     * @param mixed $sessionId Sessionid.
     * @param mixed $payload Payload.
     * @param mixed $status Status.
     */
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

        $payloadHash = hash('sha256', $payload);
        $businessEventKey = $this->buildBusinessEventKey($eventType, $sessionId);
        $matchedByEventId = true;
        $event = $this->findByEventId($eventId);
        if ($event === null && $businessEventKey !== null) {
            $event = $this->findByBusinessEventKey($businessEventKey);
            $matchedByEventId = false;
        } elseif ($event !== null) {
            $businessCollision = $this->findExistingBusinessCollision($event, $businessEventKey);
            if ($businessCollision !== null) {
                $event = $businessCollision;
                $matchedByEventId = false;
            }
        }
        $now = DateTime::now();

        if ($event === null) {
            $event = $this->eventsTable->newEmptyEntity();
            $event->event_id = $eventId;
            $event->first_seen_at = $now;
            $event->processing_started_at = $now;
            $matchedByEventId = true;
        }

        if ($matchedByEventId && $this->hasMismatchedBusinessKey($event, $businessEventKey)) {
            $this->markEventSuspicious(
                $event,
                $now,
                $payloadHash,
                'status_update_business_key_mismatch',
                $businessEventKey,
                $status,
            );

            return;
        }

        if ($this->isSuspiciousEvent($event)) {
            $this->recordSuppressedStatusUpdate($event, $eventId, $status, $payloadHash, $now);
            if ($matchedByEventId) {
                $this->touchExistingEvent($event, $now, $payloadHash);
            } else {
                $this->touchBusinessDuplicateEvent(
                    $event,
                    $eventId,
                    $now,
                    $payloadHash,
                    'suspicious_status_update',
                );
            }

            return;
        }

        $event->event_type = $eventType;
        $event->session_id = $sessionId;
        if ($businessEventKey !== null) {
            $event->business_event_key = $businessEventKey;
        }
        if ($matchedByEventId) {
            $this->preservePayloadHash($event, $payloadHash);
        } else {
            $this->applyBusinessReplayAudit($event, $eventId, $payloadHash, $now);
            $this->logBusinessEventReplay($event, $eventId, 'status_update');
        }
        $event->processing_status = $status;
        $event->last_seen_at = $now;

        $this->eventsTable->saveOrFail($event);
    }

    /**
     * Find by event id.
     *
     * @param mixed $eventId Eventid.
     */
    private function findByEventId(string $eventId): ?object
    {
        return $this->eventsTable->find()
            ->where(['StripeWebhookEvents.event_id' => $eventId])
            ->first();
    }

    /**
     * Find processed business duplicate.
     *
     * @param mixed $eventType Eventtype.
     * @param mixed $sessionId Sessionid.
     */
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

    /**
     * Handle existing event.
     *
     * @param mixed $event Event.
     * @param mixed $eventType Eventtype.
     * @param mixed $sessionId Sessionid.
     * @param mixed $businessEventKey Businesseventkey.
     * @param mixed $payloadHash Payloadhash.
     * @param mixed $now Now.
     */
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
            $this->hasMismatchedBusinessKey($event, $businessEventKey)
        ) {
            $this->markEventSuspicious(
                $event,
                $now,
                $payloadHash,
                'event_id_business_key_mismatch',
                $businessEventKey,
            );

            return self::RESULT_SUSPICIOUS;
        }

        if ($this->isSuspiciousEvent($event)) {
            $this->touchExistingEvent($event, $now, $payloadHash);

            return self::RESULT_SUSPICIOUS;
        }

        if ($status === 'processing') {
            $processedBusinessDuplicate = $this->findProcessedBusinessDuplicate($eventType, $sessionId);
            if (
                $processedBusinessDuplicate !== null &&
                (string)$processedBusinessDuplicate->event_id !== (string)$event->event_id
            ) {
                $this->touchExistingEvent($event, $now, $payloadHash);

                return self::RESULT_DUPLICATE;
            }

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

    /**
     * Claim existing event.
     *
     * @param mixed $event Event.
     * @param mixed $now Now.
     * @param mixed $payloadHash Payloadhash.
     * @param mixed $onlyIfStaleProcessing Onlyifstaleprocessing.
     */
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
        $conditions['suspicious_state'] = 'clean';

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
            if ($this->isSuspiciousEvent($reloaded)) {
                $this->touchExistingEvent($reloaded, $now, $payloadHash);

                return self::RESULT_SUSPICIOUS;
            }

            $this->touchExistingEvent($reloaded, $now, $payloadHash);
        }

        return self::RESULT_IN_PROGRESS;
    }

    /**
     * Touch existing event.
     *
     * @param mixed $event Event.
     * @param mixed $now Now.
     * @param mixed $payloadHash Payloadhash.
     */
    private function touchExistingEvent(object $event, DateTime $now, string $payloadHash): void
    {
        $this->touchExistingEventInternal($event, $now, $payloadHash, 'duplicate', true);
    }

    /**
     * Touch business duplicate event.
     *
     * @param mixed $event Event.
     * @param mixed $incomingEventId Incomingeventid.
     * @param mixed $now Now.
     * @param mixed $payloadHash Payloadhash.
     * @param mixed $context Context.
     */
    private function touchBusinessDuplicateEvent(
        object $event,
        string $incomingEventId,
        DateTime $now,
        string $payloadHash,
        string $context,
    ): void {
        $this->applyBusinessReplayAudit($event, $incomingEventId, $payloadHash, $now);
        $this->logBusinessEventReplay($event, $incomingEventId, $context);
        $this->touchExistingEventInternal($event, $now, $payloadHash, $context, false);
    }

    /**
     * Touch existing event internal.
     *
     * @param mixed $event Event.
     * @param mixed $now Now.
     * @param mixed $payloadHash Payloadhash.
     * @param mixed $context Context.
     * @param mixed $logPayloadMismatch Logpayloadmismatch.
     */
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

    /**
     * Handle business event collision.
     *
     * @param mixed $event Event.
     * @param mixed $incomingEventId Incomingeventid.
     * @param mixed $eventType Eventtype.
     * @param mixed $sessionId Sessionid.
     * @param mixed $businessEventKey Businesseventkey.
     * @param mixed $payloadHash Payloadhash.
     * @param mixed $now Now.
     */
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

        if ($this->isSuspiciousEvent($event)) {
            $this->touchBusinessDuplicateEvent($event, $incomingEventId, $now, $payloadHash, 'suspicious_business_duplicate');

            return self::RESULT_SUSPICIOUS;
        }

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

    /**
     * Claim business event.
     *
     * @param mixed $event Event.
     * @param mixed $incomingEventId Incomingeventid.
     * @param mixed $payloadHash Payloadhash.
     * @param mixed $now Now.
     */
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
        $conditions['suspicious_state'] = 'clean';

        $updated = $this->eventsTable->updateAll([
            'processing_status' => 'processing',
            'processing_started_at' => $now,
            'last_seen_at' => $now,
        ], $conditions);

        if ($updated > 0) {
            $reloaded = $this->findByEventId((string)$event->event_id);
            if ($reloaded !== null) {
                $this->touchBusinessDuplicateEvent($reloaded, $incomingEventId, $now, $payloadHash, 'business_retry');
            } else {
                $this->logBusinessEventReplay($event, $incomingEventId, 'business_retry');
            }

            return self::RESULT_CLAIMED;
        }

        $reloaded = $this->findByBusinessEventKey((string)$event->business_event_key);
        if ($reloaded !== null) {
            if ($this->isSuspiciousEvent($reloaded)) {
                $this->touchBusinessDuplicateEvent($reloaded, $incomingEventId, $now, $payloadHash, 'suspicious_business_retry');

                return self::RESULT_SUSPICIOUS;
            }

            $this->touchBusinessDuplicateEvent($reloaded, $incomingEventId, $now, $payloadHash, 'business_retry');
        }

        return self::RESULT_IN_PROGRESS;
    }

    /**
     * Find by business event key.
     *
     * @param mixed $businessEventKey Businesseventkey.
     */
    private function findByBusinessEventKey(string $businessEventKey): ?object
    {
        return $this->eventsTable->find()
            ->where(['StripeWebhookEvents.business_event_key' => $businessEventKey])
            ->first();
    }

    /**
     * Find existing business collision.
     *
     * @param mixed $event Event.
     * @param mixed $businessEventKey Businesseventkey.
     */
    private function findExistingBusinessCollision(object $event, ?string $businessEventKey): ?object
    {
        if ($businessEventKey === null) {
            return null;
        }

        if (!$this->isDetachedLegacyEvent($event)) {
            return null;
        }

        $businessEvent = $this->findByBusinessEventKey($businessEventKey);
        if ($businessEvent === null) {
            return null;
        }

        if ((string)$businessEvent->event_id === (string)$event->event_id) {
            return null;
        }

        return $businessEvent;
    }

    /**
     * Is detached legacy event.
     *
     * @param mixed $event Event.
     */
    private function isDetachedLegacyEvent(object $event): bool
    {
        return (string)($event->business_event_key ?? '') === '';
    }

    /**
     * Has mismatched business key.
     *
     * @param mixed $event Event.
     * @param mixed $businessEventKey Businesseventkey.
     */
    private function hasMismatchedBusinessKey(object $event, ?string $businessEventKey): bool
    {
        $existingBusinessKey = (string)($event->business_event_key ?? '');

        if ($existingBusinessKey === '') {
            return false;
        }

        return $businessEventKey === null || $existingBusinessKey !== $businessEventKey;
    }

    /**
     * Is suspicious event.
     *
     * @param mixed $event Event.
     */
    private function isSuspiciousEvent(object $event): bool
    {
        return (string)($event->suspicious_state ?? 'clean') === 'suspicious';
    }

    /**
     * Mark event suspicious.
     *
     * @param mixed $event Event.
     * @param mixed $now Now.
     * @param mixed $payloadHash Payloadhash.
     * @param mixed $reasonCode Reasoncode.
     * @param mixed $incomingBusinessEventKey Incomingbusinesseventkey.
     * @param mixed $targetStatus Targetstatus.
     */
    private function markEventSuspicious(
        object $event,
        DateTime $now,
        string $payloadHash,
        string $reasonCode,
        ?string $incomingBusinessEventKey,
        ?string $targetStatus = null,
    ): void {
        $this->logPayloadHashMismatch($event, $payloadHash, $reasonCode);

        Log::warning('Stripe webhook ledger detected a suspicious business key mismatch.', [
            'event_id' => (string)$event->event_id,
            'reason_code' => $reasonCode,
            'existing_business_event_key' => (string)($event->business_event_key ?? ''),
            'incoming_business_event_key' => $incomingBusinessEventKey,
            'target_status' => $targetStatus,
        ]);

        $event->suspicious_state = 'suspicious';
        $event->suspicious_reason_code = $reasonCode;
        $event->suspicious_seen_at = $now;
        $event->suspicious_business_event_key = $incomingBusinessEventKey;
        $event->suspicious_payload_hash = $payloadHash;
        $event->suspicious_target_status = $targetStatus;
        $event->suspicious_count = max(1, (int)($event->suspicious_count ?? 0) + 1);
        $event->last_seen_at = $now;
        $this->eventsTable->saveOrFail($event);
    }

    /**
     * Record suppressed status update.
     *
     * @param mixed $event Event.
     * @param mixed $eventId Eventid.
     * @param mixed $targetStatus Targetstatus.
     * @param mixed $payloadHash Payloadhash.
     * @param mixed $now Now.
     */
    private function recordSuppressedStatusUpdate(
        object $event,
        string $eventId,
        string $targetStatus,
        string $payloadHash,
        DateTime $now,
    ): void {
        $event->last_suppressed_status_update = $targetStatus;
        $event->last_suppressed_status_event_id = $eventId !== '' ? $eventId : null;
        $event->last_suppressed_status_payload_hash = $payloadHash;
        $event->last_suppressed_status_seen_at = $now;
    }

    /**
     * Apply business replay audit.
     *
     * @param mixed $event Event.
     * @param mixed $incomingEventId Incomingeventid.
     * @param mixed $payloadHash Payloadhash.
     * @param mixed $now Now.
     */
    private function applyBusinessReplayAudit(
        object $event,
        string $incomingEventId,
        string $payloadHash,
        DateTime $now,
    ): void {
        if ($incomingEventId === '' || (string)$event->event_id === $incomingEventId) {
            return;
        }

        $event->replay_count = max(1, (int)($event->replay_count ?? 0) + 1);
        $event->last_replay_event_id = $incomingEventId;
        $event->last_replay_payload_hash = $payloadHash;
        $event->last_replay_seen_at = $now;
    }

    /**
     * Build business event key.
     *
     * @param mixed $eventType Eventtype.
     * @param mixed $sessionId Sessionid.
     */
    private function buildBusinessEventKey(string $eventType, string $sessionId): ?string
    {
        if ($sessionId === '') {
            return null;
        }

        return $eventType . ':' . $sessionId;
    }

    /**
     * Preserve payload hash.
     *
     * @param mixed $event Event.
     * @param mixed $payloadHash Payloadhash.
     */
    private function preservePayloadHash(object $event, string $payloadHash): void
    {
        $existingHash = (string)($event->payload_hash ?? '');
        if ($existingHash === '') {
            $event->payload_hash = $payloadHash;

            return;
        }

        $this->logPayloadHashMismatch($event, $payloadHash, 'status_update');
    }

    /**
     * Log payload hash mismatch.
     *
     * @param mixed $event Event.
     * @param mixed $payloadHash Payloadhash.
     * @param mixed $context Context.
     */
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

    /**
     * Log business event replay.
     *
     * @param mixed $event Event.
     * @param mixed $incomingEventId Incomingeventid.
     * @param mixed $context Context.
     */
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
