<?php
declare(strict_types=1);

namespace App\Service;

use App\Exception\Payments\ManualReviewWebhookException;
use App\Exception\Payments\PaymentWebhookException;
use Cake\Datasource\FactoryLocator;
use Cake\ORM\Locator\LocatorInterface;

class PaymentWebhookIncidentRecorder
{
    private object $incidentsTable;

    public function __construct(?LocatorInterface $tableLocator = null)
    {
        $locator = $tableLocator ?? FactoryLocator::get('Table');
        $this->incidentsTable = $locator->get('PaymentWebhookIncidents');
    }

    public function record(
        PaymentWebhookException $exception,
        string $eventType,
        object $session,
        string $payload,
        string $eventId = '',
    ): void
    {
        $context = $exception->getContext();
        $sessionId = (string)($context['session_id'] ?? $session->id ?? '');
        $reasonCode = (string)($context['reason_code'] ?? 'unknown_reason');

        $query = $this->incidentsTable->find()->where([
            'PaymentWebhookIncidents.reason_code' => $reasonCode,
        ]);
        if ($eventId !== '') {
            $query->where(['PaymentWebhookIncidents.event_id' => $eventId]);
        } else {
            $query->where([
                'PaymentWebhookIncidents.status' => 'open',
                'PaymentWebhookIncidents.event_type' => $eventType,
                'PaymentWebhookIncidents.session_id' => $sessionId,
            ]);
        }

        $incident = $query->first();

        if ($incident === null) {
            $incident = $this->incidentsTable->newEmptyEntity();
        }

        $incident = $this->incidentsTable->patchEntity($incident, [
            'event_type' => $eventType,
            'session_id' => $sessionId,
            'payment_id' => $context['payment_id'] ?? null,
            'booking_id' => $context['booking_id'] ?? null,
            'reason_code' => $reasonCode,
            'severity' => $exception instanceof ManualReviewWebhookException ? 'error' : 'warning',
            'context_json' => (string)json_encode($context, JSON_UNESCAPED_SLASHES),
            'payload_hash' => hash('sha256', $payload),
            'notes' => $exception->getMessage(),
        ]);

        if ($incident->isNew()) {
            $incident->set('status', 'open');
            $incident->set('resolved_at', null);
            $incident->set('resolved_by_admin_id', null);
        }
        $incident->set('event_id', $eventId !== '' ? $eventId : null);

        $this->incidentsTable->saveOrFail($incident);
    }
}
