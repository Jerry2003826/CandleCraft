<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Core\Configure;

class StripeCheckoutGateway implements StripeCheckoutGatewayInterface
{
    public function __construct(
        private readonly ?string $secretKey = null,
    ) {
    }

    public function createCheckoutSession(array $payload): object
    {
        $this->bootstrap();

        return \Stripe\Checkout\Session::create($payload);
    }

    public function retrieveCheckoutSession(string $sessionId): object
    {
        $this->bootstrap();

        return \Stripe\Checkout\Session::retrieve($sessionId);
    }

    public function expireCheckoutSession(string $sessionId): object
    {
        $this->bootstrap();

        return \Stripe\Checkout\Session::expire($sessionId);
    }

    private function bootstrap(): void
    {
        \Stripe\Stripe::setApiKey($this->secretKey ?? (string)Configure::read('Stripe.secret_key'));
    }
}
