<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\PaymentConfirmationService;
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
                (new PaymentConfirmationService())->confirmCheckoutSession($event->data->object);
            } catch (RuntimeException $exception) {
                Log::warning('Stripe webhook completed session could not be fully applied: ' . json_encode([
                    'event_type' => $event->type,
                    'session_id' => (string)($event->data->object->id ?? ''),
                    'error' => $exception->getMessage(),
                ]));
            }
        }

        return $this->jsonResponse(200, ['received' => true]);
    }

    private function jsonResponse(int $status, array $payload): Response
    {
        return $this->response
            ->withStatus($status)
            ->withType('application/json')
            ->withStringBody((string)json_encode($payload));
    }
}
