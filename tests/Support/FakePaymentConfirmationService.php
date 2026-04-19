<?php
declare(strict_types=1);

namespace App\Test\Support;

use App\Service\PaymentConfirmationServiceInterface;

class FakePaymentConfirmationService implements PaymentConfirmationServiceInterface
{
    public static array $receivedSessions = [];
    public static array $failedSessions = [];
    public static $handler = null;
    public static $failureHandler = null;

    public static function reset(): void
    {
        self::$receivedSessions = [];
        self::$failedSessions = [];
        self::$handler = null;
        self::$failureHandler = null;
    }

    public function confirmCheckoutSession(object $session): string
    {
        self::$receivedSessions[] = $session;

        if (is_callable(self::$handler)) {
            return (self::$handler)($session);
        }

        return 'confirmed';
    }

    public function markCheckoutSessionFailed(object $session): string
    {
        self::$failedSessions[] = $session;

        if (is_callable(self::$failureHandler)) {
            return (self::$failureHandler)($session);
        }

        return 'failed';
    }
}
