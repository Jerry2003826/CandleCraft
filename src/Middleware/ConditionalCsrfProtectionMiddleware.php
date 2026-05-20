<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Support\DebugKitRequestMatcher;
use App\Support\WebhookRequestMatcher;
use Cake\Http\Middleware\CsrfProtectionMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ConditionalCsrfProtectionMiddleware implements MiddlewareInterface
{
    /**
     * Construct.
     *
     * @param mixed $csrfProtectionMiddleware Csrfprotectionmiddleware.
     * @return mixed
     */
    public function __construct(
        private readonly CsrfProtectionMiddleware $csrfProtectionMiddleware,
    ) {
    }

    /**
     * Process.
     *
     * @param mixed $request Request.
     * @param mixed $handler Handler.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (
            WebhookRequestMatcher::isStripeWebhookRequest($request)
            || DebugKitRequestMatcher::isDebugKitRequest($request)
        ) {
            return $handler->handle($request);
        }

        return $this->csrfProtectionMiddleware->process($request, $handler);
    }
}
