<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Core\Configure;
use Stripe\Checkout\Session;

class StripeCheckoutGateway implements StripeCheckoutGatewayInterface
{
    /**
     * Construct.
     *
     * @param mixed $secretKey Secretkey.
     * @return mixed
     */
    public function __construct(
        private readonly ?string $secretKey = null,
    ) {
    }

    /**
     * Create checkout session.
     *
     * @param mixed $payload Payload.
     */
    public function createCheckoutSession(array $payload): object
    {
        $this->bootstrap();

        return Session::create($payload);
    }

    /**
     * Retrieve checkout session.
     *
     * @param mixed $sessionId Sessionid.
     */
    public function retrieveCheckoutSession(string $sessionId): object
    {
        $this->bootstrap();

        return Session::retrieve($sessionId);
    }

    /**
     * Expire checkout session.
     *
     * @param mixed $sessionId Sessionid.
     */
    public function expireCheckoutSession(string $sessionId): object
    {
        $this->bootstrap();

        return Session::expire($sessionId);
    }

    /**
     * Bootstrap.
     */
    private function bootstrap(): void
    {
        StripeApiBootstrap::configure($this->secretKey ?? (string)Configure::read('Stripe.secret_key'));
    }
}
