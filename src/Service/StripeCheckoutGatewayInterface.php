<?php
declare(strict_types=1);

namespace App\Service;

interface StripeCheckoutGatewayInterface
{
    /**
     * Create checkout session.
     *
     * @param mixed $payload Payload.
     */
    public function createCheckoutSession(array $payload): object;

    /**
     * Retrieve checkout session.
     *
     * @param mixed $sessionId Sessionid.
     */
    public function retrieveCheckoutSession(string $sessionId): object;

    /**
     * Expire checkout session.
     *
     * @param mixed $sessionId Sessionid.
     */
    public function expireCheckoutSession(string $sessionId): object;
}
