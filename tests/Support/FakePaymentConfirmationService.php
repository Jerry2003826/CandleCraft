<?php
declare(strict_types=1);

namespace App\Test\Support;

use App\Service\PaymentConfirmationServiceInterface;

class FakePaymentConfirmationService implements PaymentConfirmationServiceInterface
{
    public static array $receivedSessions = [];
    public static $handler = null;

    public static function reset(): void
    {
        self::$receivedSessions = [];
        self::$handler = null;
    }

    public function confirmCheckoutSession(object $session): string
    {
        self::$receivedSessions[] = $session;

        if (is_callable(self::$handler)) {
            return (self::$handler)($session);
        }

        return 'confirmed';
    }
}
