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

            $referer = $request->getHeaderLine('Referer');
            $target = $referer ?: '/login';

            return $response
                ->withHeader('Location', $target)
                ->withStatus(302);
        }
    }
}
