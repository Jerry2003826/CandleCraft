<?php
declare(strict_types=1);

namespace App\Test\Support;

use App\Service\StripeRefundGatewayInterface;

class FakeStripeRefundGateway implements StripeRefundGatewayInterface
{
    public static array $createdPayloads = [];
    public static $createHandler = null;

    public static function reset(): void
    {
        self::$createdPayloads = [];
        self::$createHandler = null;
    }

    public function createRefund(array $payload): object
    {
        self::$createdPayloads[] = $payload;

        if (is_callable(self::$createHandler)) {
            return (self::$createHandler)($payload);
        }

        return (object)[
            'id' => 're_fake_default',
            'amount' => $payload['amount'] ?? 0,
            'currency' => 'aud',
            'status' => 'succeeded',
            'reason' => $payload['reason'] ?? 'requested_by_customer',
            'charge' => $payload['charge'] ?? 'ch_fake_default',
            'payment_intent' => $payload['payment_intent'] ?? 'pi_fake_default',
        ];
    }
}
