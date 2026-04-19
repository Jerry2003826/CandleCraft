<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Core\Configure;

final class StripeConfiguration
{
    public static function isCheckoutReady(): bool
    {
        return self::configuredStripeMode() !== null
            && self::hasUsableSecretKey()
            && self::hasUsableWebhookSecret();
    }

    public static function canManageCheckoutSessions(): bool
    {
        return self::hasUsableSecretKey();
    }

    public static function hasUsableSecretKey(?string $value = null): bool
    {
        $key = trim($value ?? (string)Configure::read('Stripe.secret_key'));
        if (!self::isUsableValue($key, ['sk_test_', 'sk_live_', 'rk_test_', 'rk_live_'])) {
            return false;
        }

        $configuredMode = self::configuredStripeMode();
        if ($configuredMode === 'live') {
            return str_starts_with($key, 'sk_live_') || str_starts_with($key, 'rk_live_');
        }

        if ($configuredMode === 'test') {
            return str_starts_with($key, 'sk_test_') || str_starts_with($key, 'rk_test_');
        }

        return false;
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

                return true;
            }
        }

        return false;
    }

    private static function configuredStripeMode(): ?string
    {
        $configuredEnvironment = Configure::read('Stripe.environment');
        if (is_string($configuredEnvironment) && trim($configuredEnvironment) !== '') {
            return self::normalizeStripeMode($configuredEnvironment);
        }

        $environment = env('STRIPE_ENVIRONMENT') ?: env('APP_ENV') ?: env('CAKEPHP_ENV') ?: '';
        if (is_string($environment) && trim($environment) !== '') {
            return self::normalizeStripeMode($environment);
        }

        if (Configure::read('debug') === false && PHP_SAPI !== 'cli') {
            return 'live';
        }

        return 'test';
    }

    private static function normalizeStripeMode(string $value): ?string
    {
        $normalized = strtolower(trim($value));

        return match ($normalized) {
            'live', 'production', 'prod' => 'live',
            'test', 'staging', 'uat', 'sandbox', 'development', 'dev' => 'test',
            default => null,
        };
    }
}
