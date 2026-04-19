<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Core\Configure;

final class StripeConfiguration
{
    public static function isCheckoutReady(): bool
    {
        return self::hasUsableSecretKey() && self::hasUsableWebhookSecret();
    }

    public static function canManageCheckoutSessions(): bool
    {
        return self::hasUsableSecretKey();
    }

    public static function hasUsableSecretKey(?string $value = null): bool
    {
        return self::isUsableValue($value ?? (string)Configure::read('Stripe.secret_key'), ['sk_', 'rk_']);
    }

    public static function hasUsableWebhookSecret(?string $value = null): bool
    {
        return self::isUsableValue($value ?? (string)Configure::read('Stripe.webhook_secret'), 'whsec_');
    }

    private static function isUsableValue(string $value, array|string $requiredPrefixes): bool
    {
        $normalized = trim($value);
        if ($normalized === '') {
            return false;
        }

        foreach ((array)$requiredPrefixes as $requiredPrefix) {
            if (str_starts_with($normalized, $requiredPrefix)) {
                return !str_contains(strtolower($normalized), 'placeholder');
            }
        }

        return false;
    }
}
