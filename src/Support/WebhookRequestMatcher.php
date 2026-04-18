<?php
declare(strict_types=1);

namespace App\Support;

use Psr\Http\Message\ServerRequestInterface;

final class WebhookRequestMatcher
{
    /**
     * @var list<string>
     */
    private const EXCLUDED_PATHS = [
        '/stripe/webhook',
        '/consumer/payments/webhook',
        '/student/payments/webhook',
    ];

    public static function isStripeWebhookRequest(ServerRequestInterface $request): bool
    {
        $params = (array)$request->getAttribute('params', []);
        $controller = (string)($params['controller'] ?? '');
        $action = (string)($params['action'] ?? '');

        if ($controller === 'StripeWebhooks' && $action === 'checkout') {
            return true;
        }

        $base = rtrim((string)$request->getAttribute('base', ''), '/');
        $candidates = [
            (string)$request->getUri()->getPath(),
            (string)$request->getRequestTarget(),
        ];

        foreach ($candidates as $candidate) {
            $normalized = self::normalizePath($candidate);
            if (in_array($normalized, self::EXCLUDED_PATHS, true)) {
                return true;
            }

            if ($base !== '' && str_starts_with($normalized, $base . '/')) {
                $stripped = substr($normalized, strlen($base));
                if (in_array($stripped, self::EXCLUDED_PATHS, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    private static function normalizePath(string $candidate): string
    {
        $normalized = strtok($candidate, '?') ?: '';

        return '/' . ltrim($normalized, '/');
    }
}
