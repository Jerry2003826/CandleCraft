<?php
declare(strict_types=1);

namespace App\Controller;

use App\Exception\Payments\ManualReviewWebhookException;
use App\Exception\Payments\NonRetriableWebhookException;
use App\Exception\Payments\PaymentWebhookException;
use App\Exception\Payments\RetriableWebhookException;
use App\Service\PaymentAdminAlertService;
use App\Service\PaymentConfirmationService;
use App\Service\PaymentConfirmationServiceInterface;
use App\Service\PaymentDisputeService;
use App\Service\PaymentRefundService;
use App\Service\PaymentWebhookIncidentRecorder;
use App\Service\StripeWebhookEventLedger;
use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\Log\Log;
use RuntimeException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Throwable;
use UnexpectedValueException;

class StripeWebhooksController extends Controller
{
    /**
     * Checkout.
     */
    public function checkout(): Response
    {
        $this->request->allowMethod(['post']);

        $payload = (string)$this->request->getBody();
        $sigHeader = $this->request->getHeaderLine('Stripe-Signature');
        $endpointSecret = (string)Configure::read('Stripe.webhook_secret');

        if ($endpointSecret === '') {
            return $this->jsonResponse(400, ['error' => 'Stripe webhook secret is not configured.']);
        }

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (UnexpectedValueException | SignatureVerificationException $exception) {
            return $this->jsonResponse(400, ['error' => 'Invalid Stripe webhook signature.']);
        }

        $eventId = $this->extractEventId($event, $payload);
        $eventType = (string)$event->type;
        $session = $event->data->object;
        $sessionId = (string)($session->id ?? '');
        $ledger = new StripeWebhookEventLedger();

        $claimResult = $ledger->beginProcessing($eventId, $eventType, $sessionId, $payload);
        if ($claimResult === StripeWebhookEventLedger::RESULT_DUPLICATE) {
            return $this->jsonResponse(200, ['received' => true, 'duplicate' => true]);
        }
        if ($claimResult === StripeWebhookEventLedger::RESULT_SUSPICIOUS) {
            return $this->jsonResponse(200, ['received' => true, 'suspicious' => true]);
        }
        if ($claimResult === StripeWebhookEventLedger::RESULT_IN_PROGRESS) {
            return $this->jsonResponse(503, ['error' => 'Webhook event is already being processed.']);
        }

        try {
            $outcome = $this->handleWebhookEvent($event);
            if ($outcome === 'ignored') {
                $ledger->markIgnored($eventId, $eventType, $sessionId, $payload);
            } else {
                $ledger->markProcessed($eventId, $eventType, $sessionId, $payload);
            }
        } catch (RetriableWebhookException $exception) {
            $ledger->markFailed($eventId, $eventType, $sessionId, $payload);
            $this->logWebhookFailure('error', $eventType, $session, $exception);
            $this->alertAdmins(
                'Stripe webhook processing failed',
                'A Stripe webhook failed with a retriable error and will need Stripe retry processing.',
                [
                    'event_type' => $eventType,
                    'event_id' => $eventId,
                    'session_id' => $sessionId,
                    'reason_code' => $exception->getContext()['reason_code'] ?? 'unknown_reason',
                ],
            );

            return $this->jsonResponse(500, ['error' => 'Temporary webhook processing failure.']);
        } catch (ManualReviewWebhookException | NonRetriableWebhookException $exception) {
            $level = $exception instanceof ManualReviewWebhookException ? 'error' : 'warning';
            $this->logWebhookFailure($level, $eventType, $session, $exception);
            try {
                $this->persistWebhookIncident($exception, $eventType, $session, $payload, $eventId);
                $ledger->markProcessed($eventId, $eventType, $sessionId, $payload);
            } catch (Throwable $recordingException) {
                $ledger->markFailed($eventId, $eventType, $sessionId, $payload);
                Log::error('Unable to persist Stripe webhook incident: ' . json_encode([
                    'event_type' => $eventType,
                    'event_id' => $eventId,
                    'session_id' => $sessionId,
                    'reason_code' => $exception->getContext()['reason_code'] ?? 'unknown_reason',
                    'error' => $recordingException->getMessage(),
                ]));

                return $this->jsonResponse(500, ['error' => 'Temporary webhook processing failure.']);
            }
        } catch (RuntimeException $exception) {
            $wrapped = new RetriableWebhookException($exception->getMessage(), [
                'event_type' => $eventType,
                'session_id' => $sessionId,
                'reason_code' => 'unexpected_runtime_exception',
            ], previous: $exception);
            $ledger->markFailed($eventId, $eventType, $sessionId, $payload);
            $this->logWebhookFailure('error', $eventType, $session, $wrapped);

            return $this->jsonResponse(500, ['error' => 'Temporary webhook processing failure.']);
        }

        return $this->jsonResponse(200, ['received' => true]);
    }

    /**
     * Handle webhook event.
     *
     * @param mixed $event Event.
     */
    private function handleWebhookEvent(object $event): string
    {
        switch ((string)$event->type) {
            case 'checkout.session.completed':
            case 'checkout.session.async_payment_succeeded':
                $this->confirmationService()->confirmCheckoutSession(
                    $event->data->object,
                    (string)$event->type,
                    'stripe_webhook',
                );

                return 'processed';

            case 'checkout.session.async_payment_failed':
                $this->confirmationService()->markCheckoutSessionFailed($event->data->object);

                return 'processed';

            case 'checkout.session.expired':
                $this->confirmationService()->markCheckoutSessionExpired($event->data->object);

                return 'processed';

            case 'refund.created':
            case 'refund.updated':
            case 'refund.failed':
            case 'charge.refund.updated':
                (new PaymentRefundService())->syncRefundObject($event->data->object);

                return 'processed';

            case 'charge.refunded':
                (new PaymentRefundService())->syncChargeRefunds($event->data->object);

                return 'processed';

            case 'charge.dispute.created':
            case 'charge.dispute.updated':
            case 'charge.dispute.closed':
            case 'charge.dispute.funds_withdrawn':
            case 'charge.dispute.funds_reinstated':
                (new PaymentDisputeService())->syncDispute($event->data->object, (string)$event->type);

                return 'processed';
        }

        return 'ignored';
    }

    /**
     * Confirmation service.
     */
    protected function confirmationService(): PaymentConfirmationServiceInterface
    {
        $className = (string)Configure::read('Payments.confirmation_service_class', PaymentConfirmationService::class);
        $service = new $className();

        if (!$service instanceof PaymentConfirmationServiceInterface) {
            throw new RuntimeException(sprintf(
                'Configured confirmation service "%s" must implement %s.',
                $className,
                PaymentConfirmationServiceInterface::class,
            ));
        }

        return $service;
    }

    /**
     * Json response.
     *
     * @param mixed $status Status.
     * @param mixed $payload Payload.
     */
    private function jsonResponse(int $status, array $payload): Response
    {
        return $this->response
            ->withStatus($status)
            ->withType('application/json')
            ->withStringBody((string)json_encode($payload));
    }

    /**
     * Extract event id.
     *
     * @param mixed $event Event.
     * @param mixed $payload Payload.
     */
    private function extractEventId(object $event, string $payload): string
    {
        $eventId = (string)($event->id ?? '');
        if ($eventId !== '') {
            return $eventId;
        }

        $decodedPayload = json_decode($payload, true);
        if (is_array($decodedPayload) && isset($decodedPayload['id']) && is_string($decodedPayload['id'])) {
            return $decodedPayload['id'];
        }

        return '';
    }

    /**
     * Log webhook failure.
     *
     * @param mixed $level Level.
     * @param mixed $eventType Eventtype.
     * @param mixed $session Session.
     * @param mixed $exception Exception.
     */
    private function logWebhookFailure(
        string $level,
        string $eventType,
        object $session,
        PaymentWebhookException $exception,
    ): void {
        $context = array_merge([
            'event_type' => $eventType,
            'session_id' => (string)($session->id ?? ''),
            'error' => $exception->getMessage(),
        ], $exception->getContext());

        if ($level === 'error') {
            Log::error('Stripe webhook event requires intervention: ' . json_encode($context));

            return;
        }

        Log::warning('Stripe webhook event could not be fully applied: ' . json_encode($context));
    }

    /**
     * Persist webhook incident.
     *
     * @param mixed $exception Exception.
     * @param mixed $eventType Eventtype.
     * @param mixed $session Session.
     * @param mixed $payload Payload.
     * @param mixed $eventId Eventid.
     */
    private function persistWebhookIncident(
        PaymentWebhookException $exception,
        string $eventType,
        object $session,
        string $payload,
        string $eventId,
    ): void {
        (new PaymentWebhookIncidentRecorder())->record($exception, $eventType, $session, $payload, $eventId);
    }

    /**
     * Alert admins.
     *
     * @param mixed $title Title.
     * @param mixed $message Message.
     * @param mixed $context Context.
     */
    private function alertAdmins(string $title, string $message, array $context): void
    {
        (new PaymentAdminAlertService())->alert($title, $message, $context);
    }
}
