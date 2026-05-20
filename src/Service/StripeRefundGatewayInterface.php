<?php
declare(strict_types=1);

namespace App\Service;

interface StripeRefundGatewayInterface
{
    /**
     * Create refund.
     *
     * @param mixed $payload Payload.
     */
    public function createRefund(array $payload): object;
}
