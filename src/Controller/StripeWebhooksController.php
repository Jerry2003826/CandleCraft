<?php
declare(strict_types=1);

namespace App\Controller;

use App\Exception\Payments\ManualReviewWebhookException;
use App\Exception\Payments\NonRetriableWebhookException;
use App\Exception\Payments\PaymentWebhookException;
use App\Exception\Payments\RetriableWebhookException;
use App\Service\PaymentConfirmationService;
use App\Service\PaymentConfirmationServiceInterface;
use App\Service\PaymentWebhookIncidentRecorder;
use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\Log\Log;
use RuntimeException;

class StripeWebhooksController extends Controller
{
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
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (\UnexpectedValueException|\Stripe\Exception\SignatureVerificationException $exception) {
            return $this->jsonResponse(400, ['error' => 'Invalid Stripe webhook signature.']);
        }

        try {
            $this->handleWebhookEvent($event);
        } catch (RetriableWebhookException $exception) {
            $this->logWebhookFailure('error', $event->type, $event->data->object, $exception);

            return $this->jsonResponse(500, ['error' => 'Temporary webhook processing failure.']);
        } catch (ManualReviewWebhookException|NonRetriableWebhookException $exception) {
            $level = $exception instanceof ManualReviewWebhookException ? 'error' : 'warning';
            $this->logWebhookFailure($level, $event->type, $event->data->object, $exception);
            $this->persistWebhookIncident($exception, $event->type, $event->data->object, $payload, $this->extractEventId($event, $payload));
        } catch (RuntimeException $exception) {
            $wrapped = new RetriableWebhookException($exception->getMessage(), [
                'event_type' => $event->type,
                'session_id' => (string)($event->data->object->id ?? ''),
                'reason_code' => 'unexpected_runtime_exception',
            ], previous: $exception);
            $this->logWebhookFailure('error', $event->type, $event->data->object, $wrapped);

            return $this->jsonResponse(500, ['error' => 'Temporary webhook processing failure.']);
        }

        return $this->jsonResponse(200, ['received' => true]);
    }

    private function handleWebhookEvent(object $event): void
    {
        switch ((string)$event->type) {
            case 'checkout.session.completed':
            case 'checkout.session.async_payment_succeeded':
                $this->confirmationService()->confirmCheckoutSession(
                    $event->data->object,
                    (string)$event->type,
                    'stripe_webhook'
                );
                break;

            case 'checkout.session.async_payment_failed':
                $this->confirmationService()->markCheckoutSessionFailed($event->data->object);
                break;

            case 'checkout.session.expired':
                $this->confirmationService()->markCheckoutSessionExpired($event->data->object);
                break;
        }
    }

    protected function confirmationService(): PaymentConfirmationServiceInterface
    {
        $className = (string)Configure::read('Payments.confirmation_service_class', PaymentConfirmationService::class);
        $service = new $className();

        if (!$service instanceof PaymentConfirmationServiceInterface) {
            throw new RuntimeException(sprintf(
                'Configured confirmation service "%s" must implement %s.',
                $className,
                PaymentConfirmationServiceInterface::class
            ));
        }

        return $service;
    }

    private function jsonResponse(int $status, array $payload): Response
    {
        return $this->response
            ->withStatus($status)
            ->withType('application/json')
            ->withStringBody((string)json_encode($payload));
    }

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

    private function persistWebhookIncident(
        PaymentWebhookException $exception,
        string $eventType,
        object $session,
        string $payload,
        string $eventId,
    ): void {
        try {
            (new PaymentWebhookIncidentRecorder())->record($exception, $eventType, $session, $payload, $eventId);
        } catch (\Throwable $recordingException) {
            Log::error('Unable to persist Stripe webhook incident: ' . json_encode([
                'event_type' => $eventType,
                'event_id' => $eventId,
                'session_id' => (string)($session->id ?? ''),
                'reason_code' => $exception->getContext()['reason_code'] ?? 'unknown_reason',
                'error' => $recordingException->getMessage(),
            ]));
        }
    }
}
