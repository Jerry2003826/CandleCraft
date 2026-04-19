<?php
declare(strict_types=1);

namespace App\Middleware;

use Cake\Http\Cookie\Cookie;
use Cake\Http\Exception\InvalidCsrfTokenException;
use Cake\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Catches CSRF token mismatches (e.g. after server restart) and
 * redirects back so the browser picks up a fresh token cookie.
 */
class CsrfRetryMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (InvalidCsrfTokenException $e) {
            $response = new Response();

            $cookie = new Cookie('csrfToken', '', null, '/');
            $response = $response->withExpiredCookie($cookie);

            $target = $this->resolveRetryTarget($request);

            return $response
                ->withHeader('Location', $target)
                ->withStatus(302);
        }
    }

    private function resolveRetryTarget(ServerRequestInterface $request): string
    {
        $referer = trim($request->getHeaderLine('Referer'));
        if ($referer === '') {
            return '/login';
        }

        $parts = parse_url($referer);
        if ($parts === false) {
            return '/login';
        }

        $path = (string)($parts['path'] ?? '');
        if ($path === '' || !str_starts_with($path, '/')) {
            return '/login';
        }

        $requestHost = strtolower((string)$request->getUri()->getHost());
        $requestScheme = strtolower((string)$request->getUri()->getScheme());
        $refererHost = strtolower((string)($parts['host'] ?? $requestHost));
        $refererScheme = strtolower((string)($parts['scheme'] ?? $requestScheme));

        if (($parts['host'] ?? null) !== null && $refererHost !== $requestHost) {
            return '/login';
        }

        if (($parts['scheme'] ?? null) !== null && $requestScheme !== '' && $refererScheme !== $requestScheme) {
            return '/login';
        }

        $query = isset($parts['query']) && $parts['query'] !== '' ? '?' . $parts['query'] : '';

        return $path . $query;
    }
}
