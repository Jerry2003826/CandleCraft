<?php
declare(strict_types=1);

namespace App\Support;

use Cake\Core\Configure;
use Psr\Http\Message\ServerRequestInterface;

final class DebugKitRequestMatcher
{
    private const PATH_PREFIX = '/debug-kit';

    /**
     * Is debug kit request.
     *
     * @param mixed $request Request.
     */
    public static function isDebugKitRequest(ServerRequestInterface $request): bool
    {
        if (!Configure::read('debug')) {
            return false;
        }

        $params = (array)$request->getAttribute('params', []);
        if ((string)($params['plugin'] ?? '') === 'DebugKit') {
            return true;
        }

        $base = rtrim((string)$request->getAttribute('base', ''), '/');
        $candidates = [
            (string)$request->getUri()->getPath(),
            (string)$request->getRequestTarget(),
        ];

        foreach ($candidates as $candidate) {
            $normalized = self::normalizePath($candidate);
            if (
                $normalized === self::PATH_PREFIX
                || str_starts_with($normalized, self::PATH_PREFIX . '/')
            ) {
                return true;
            }

            if (
                $base !== ''
                && (
                    $normalized === $base . self::PATH_PREFIX
                    || str_starts_with($normalized, $base . self::PATH_PREFIX . '/')
                )
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normalize path.
     *
     * @param mixed $candidate Candidate.
     */
    private static function normalizePath(string $candidate): string
    {
        $normalized = strtok($candidate, '?') ?: '';

        return '/' . ltrim($normalized, '/');
    }
}
