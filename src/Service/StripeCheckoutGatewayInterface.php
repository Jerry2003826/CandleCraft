<?php
declare(strict_types=1);

namespace App\Service;

interface StripeCheckoutGatewayInterface
{
    public function createCheckoutSession(array $payload): object;

    public function retrieveCheckoutSession(string $sessionId): object;

    public function expireCheckoutSession(string $sessionId): object;
}
