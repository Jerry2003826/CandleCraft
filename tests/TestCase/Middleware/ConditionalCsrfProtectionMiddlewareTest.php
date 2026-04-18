<?php
declare(strict_types=1);

namespace App\Test\TestCase\Middleware;

use App\Middleware\ConditionalCsrfProtectionMiddleware;
use Cake\Http\Middleware\CsrfProtectionMiddleware;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ConditionalCsrfProtectionMiddlewareTest extends TestCase
{
    public function testWebhookBypassesCsrfInSubdirectoryDeployment(): void
    {
        $csrfMiddleware = $this->createMock(CsrfProtectionMiddleware::class);
        $csrfMiddleware->expects($this->never())->method('process');

        $middleware = new ConditionalCsrfProtectionMiddleware($csrfMiddleware);
        $request = (new ServerRequest(['url' => '/candlecraft/stripe/webhook']))
            ->withAttribute('base', '/candlecraft')
            ->withAttribute('params', [
                'controller' => 'StripeWebhooks',
                'action' => 'checkout',
            ]);
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
