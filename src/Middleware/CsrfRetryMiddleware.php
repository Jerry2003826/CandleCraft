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
            return $this->loginPath($request);
        }

        $parts = parse_url($referer);
        if ($parts === false) {
            return $this->loginPath($request);
        }

        $path = (string)($parts['path'] ?? '');
        if ($path === '' || !str_starts_with($path, '/')) {
            return $this->loginPath($request);
        }

        $requestHost = strtolower((string)$request->getUri()->getHost());
        $requestScheme = strtolower((string)$request->getUri()->getScheme());
        $refererHost = strtolower((string)($parts['host'] ?? $requestHost));
        $refererScheme = strtolower((string)($parts['scheme'] ?? $requestScheme));

        if (($parts['host'] ?? null) !== null && $refererHost !== $requestHost) {
            return $this->loginPath($request);
        }

        if (($parts['scheme'] ?? null) !== null && $requestScheme !== '' && $refererScheme !== $requestScheme) {
            return $this->loginPath($request);
        }

        $query = isset($parts['query']) && $parts['query'] !== '' ? '?' . $parts['query'] : '';

        return $path . $query;
    }

    private function loginPath(ServerRequestInterface $request): string
    {
        $base = rtrim((string)$request->getAttribute('base', ''), '/');

        return ($base !== '' ? $base : '') . '/login';
    }
}
