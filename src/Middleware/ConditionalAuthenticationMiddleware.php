<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Support\WebhookRequestMatcher;
use Authentication\Middleware\AuthenticationMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ConditionalAuthenticationMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly AuthenticationMiddleware $authenticationMiddleware,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (WebhookRequestMatcher::isStripeWebhookRequest($request)) {
            return $handler->handle($request);
        }

        return $this->authenticationMiddleware->process($request, $handler);
    }
}
