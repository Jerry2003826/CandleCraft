<?php
declare(strict_types=1);

namespace App\Middleware;

use Cake\Http\Middleware\CsrfProtectionMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ConditionalCsrfProtectionMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly CsrfProtectionMiddleware $csrfProtectionMiddleware,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = '/' . ltrim((string)$request->getUri()->getPath(), '/');
        $excludedPaths = [
            '/stripe/webhook',
            '/consumer/payments/webhook',
            '/student/payments/webhook',
        ];

        if (in_array($path, $excludedPaths, true)) {
            return $handler->handle($request);
        }

        return $this->csrfProtectionMiddleware->process($request, $handler);
    }
}
