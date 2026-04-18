<?php
declare(strict_types=1);

namespace App\Test\TestCase\Middleware;

use App\Middleware\ConditionalAuthenticationMiddleware;
use Authentication\Middleware\AuthenticationMiddleware;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ConditionalAuthenticationMiddlewareTest extends TestCase
{
    public function testWebhookBypassesAuthAtRootPath(): void
    {
        $authMiddleware = $this->createMock(AuthenticationMiddleware::class);
        $authMiddleware->expects($this->never())->method('process');

        $middleware = new ConditionalAuthenticationMiddleware($authMiddleware);
        $request = new ServerRequest(['url' => '/stripe/webhook']);
        $response = $middleware->process($request, $this->successHandler('handled'));

        $this->assertSame('handled', (string)$response->getBody());
    }

    public function testWebhookBypassesAuthInSubdirectoryDeployment(): void
    {
        $authMiddleware = $this->createMock(AuthenticationMiddleware::class);
        $authMiddleware->expects($this->never())->method('process');

        $middleware = new ConditionalAuthenticationMiddleware($authMiddleware);
        $request = (new ServerRequest(['url' => '/candlecraft/stripe/webhook']))
            ->withAttribute('base', '/candlecraft');
        $response = $middleware->process($request, $this->successHandler('handled'));

        $this->assertSame('handled', (string)$response->getBody());
    }

    private function successHandler(string $body): RequestHandlerInterface
    {
        return new class ($body) implements RequestHandlerInterface {
            public function __construct(private readonly string $body)
            {
            }

            public function handle(\Psr\Http\Message\ServerRequestInterface $request): ResponseInterface
            {
                return (new Response())->withStringBody($this->body);
            }
        };
    }
}
