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
        return self::isUsableValue(
            $value ?? (string)Configure::read('Stripe.secret_key'),
            ['sk_test_', 'sk_live_', 'rk_test_', 'rk_live_']
        );
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
                if (str_contains(strtolower($normalized), 'placeholder')) {
                    return false;
                }

                if (
                    self::shouldRejectTestSecretKeys() &&
                    in_array($requiredPrefix, ['sk_test_', 'rk_test_'], true)
                ) {
                    return false;
                }

                return true;
            }
        }

        return false;
    }

    private static function shouldRejectTestSecretKeys(): bool
    {
        $environment = strtolower(trim((string)(env('APP_ENV') ?: env('CAKEPHP_ENV') ?: '')));
        if (in_array($environment, ['prod', 'production'], true)) {
            return true;
        }

        return Configure::read('debug') === false && PHP_SAPI !== 'cli';
    }
}
