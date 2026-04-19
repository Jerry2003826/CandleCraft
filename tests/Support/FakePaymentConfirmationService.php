<?php
declare(strict_types=1);

namespace App\Test\Support;

use App\Service\PaymentConfirmationServiceInterface;

class FakePaymentConfirmationService implements PaymentConfirmationServiceInterface
{
    public static array $receivedSessions = [];
    public static array $receivedEventTypes = [];
    public static array $receivedConfirmationSources = [];
    public static array $failedSessions = [];
    public static array $expiredSessions = [];
    public static $handler = null;
    public static $failureHandler = null;
    public static $expirationHandler = null;

    public static function reset(): void
    {
        self::$receivedSessions = [];
        self::$receivedEventTypes = [];
        self::$receivedConfirmationSources = [];
        self::$failedSessions = [];
        self::$expiredSessions = [];
        self::$handler = null;
        self::$failureHandler = null;
        self::$expirationHandler = null;
    }

    public function confirmCheckoutSession(
        object $session,
        string $eventType = 'checkout.session.completed',
        string $confirmationSource = 'stripe_webhook',
    ): string
    {
        self::$receivedSessions[] = $session;
        self::$receivedEventTypes[] = $eventType;
        self::$receivedConfirmationSources[] = $confirmationSource;

        if (is_callable(self::$handler)) {
            return (self::$handler)($session, $eventType, $confirmationSource);
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

    public function markCheckoutSessionExpired(object $session): string
    {
        self::$expiredSessions[] = $session;

        if (is_callable(self::$expirationHandler)) {
            return (self::$expirationHandler)($session);
        }

        return 'expired';
    }
}
