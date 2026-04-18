<?php
declare(strict_types=1);

namespace App\Controller;

use App\Exception\Payments\ManualReviewWebhookException;
use App\Exception\Payments\NonRetriableWebhookException;
use App\Exception\Payments\PaymentWebhookException;
use App\Exception\Payments\RetriableWebhookException;
use App\Service\PaymentConfirmationService;
use App\Service\PaymentConfirmationServiceInterface;
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

        if ($event->type === 'checkout.session.completed') {
            try {
                $this->confirmationService()->confirmCheckoutSession($event->data->object);
            } catch (RetriableWebhookException $exception) {
                $this->logWebhookFailure('error', $event->type, $event->data->object, $exception);

                return $this->jsonResponse(500, ['error' => 'Temporary webhook processing failure.']);
            } catch (ManualReviewWebhookException|NonRetriableWebhookException $exception) {
                $level = $exception instanceof ManualReviewWebhookException ? 'error' : 'warning';
                $this->logWebhookFailure($level, $event->type, $event->data->object, $exception);
            } catch (RuntimeException $exception) {
                $wrapped = new RetriableWebhookException($exception->getMessage(), [
                    'event_type' => $event->type,
                    'session_id' => (string)($event->data->object->id ?? ''),
                    'reason_code' => 'unexpected_runtime_exception',
                ], previous: $exception);
                $this->logWebhookFailure('error', $event->type, $event->data->object, $wrapped);

                return $this->jsonResponse(500, ['error' => 'Temporary webhook processing failure.']);
            }
        }

        return $this->jsonResponse(200, ['received' => true]);
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
            Log::error('Stripe webhook completed session requires intervention: ' . json_encode($context));

            return;
        }

        Log::warning('Stripe webhook completed session could not be fully applied: ' . json_encode($context));
    }
}
