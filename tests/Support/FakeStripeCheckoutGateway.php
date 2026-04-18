<?php
declare(strict_types=1);

namespace App\Test\Support;

use App\Service\StripeCheckoutGatewayInterface;

class FakeStripeCheckoutGateway implements StripeCheckoutGatewayInterface
{
    public static array $createdPayloads = [];
    public static array $retrievedSessionIds = [];
    public static array $expiredSessionIds = [];
    public static $createHandler = null;
    public static $retrieveHandler = null;
    public static $expireHandler = null;

    public static function reset(): void
    {
        self::$createdPayloads = [];
        self::$retrievedSessionIds = [];
        self::$expiredSessionIds = [];
        self::$createHandler = null;
        self::$retrieveHandler = null;
        self::$expireHandler = null;
    }

    public static function makeSession(string $id, array $overrides = []): object
    {
        $payload = array_merge([
            'id' => $id,
            'url' => 'https://checkout.stripe.test/' . $id,
            'status' => 'open',
            'payment_status' => 'unpaid',
            'currency' => 'aud',
            'amount_total' => 5000,
            'payment_intent' => 'pi_test_' . $id,
            'metadata' => (object)[],
        ], $overrides);

        if (is_array($payload['metadata'] ?? null)) {
            $payload['metadata'] = (object)$payload['metadata'];
        }

        return (object)$payload;
    }

    public function createCheckoutSession(array $payload): object
    {
        self::$createdPayloads[] = $payload;

        if (is_callable(self::$createHandler)) {
            return (self::$createHandler)($payload);
        }

        return self::makeSession('cs_fake_default');
    }

    public function retrieveCheckoutSession(string $sessionId): object
    {
        self::$retrievedSessionIds[] = $sessionId;

        if (is_callable(self::$retrieveHandler)) {
            return (self::$retrieveHandler)($sessionId);
        }

        return self::makeSession($sessionId);
    }

    public function expireCheckoutSession(string $sessionId): object
    {
        self::$expiredSessionIds[] = $sessionId;

        if (is_callable(self::$expireHandler)) {
            return (self::$expireHandler)($sessionId);
        }

        return self::makeSession($sessionId, ['status' => 'expired']);
    }
}
