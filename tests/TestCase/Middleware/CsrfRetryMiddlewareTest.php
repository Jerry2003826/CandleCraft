<?php
declare(strict_types=1);

namespace App\Test\TestCase\Middleware;

use App\Middleware\CsrfRetryMiddleware;
use Cake\Http\Exception\InvalidCsrfTokenException;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Laminas\Diactoros\Uri;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CsrfRetryMiddlewareTest extends TestCase
{
    public function testExternalRefererFallsBackToLogin(): void
    {
        $middleware = new CsrfRetryMiddleware();
        $request = (new ServerRequest(['url' => '/consumer/bookings']))
            ->withUri(new Uri('https://app.example/consumer/bookings'))
            ->withHeader('Referer', 'https://evil.example/phish');

        $response = $middleware->process($request, $this->invalidCsrfHandler());

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/login', $response->getHeaderLine('Location'));
    }

    public function testMissingRefererFallsBackToSubdirectoryLoginPath(): void
    {
        $middleware = new CsrfRetryMiddleware();
        $request = (new ServerRequest(['url' => '/candlecraft/consumer/bookings']))
            ->withUri(new Uri('https://app.example/candlecraft/consumer/bookings'))
            ->withAttribute('base', '/candlecraft');

        $response = $middleware->process($request, $this->invalidCsrfHandler());

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/candlecraft/login', $response->getHeaderLine('Location'));
    }

    public function testSameOriginRefererKeepsRelativePathAndQuery(): void
    {
        $middleware = new CsrfRetryMiddleware();
        $request = (new ServerRequest(['url' => '/consumer/bookings']))
            ->withUri(new Uri('https://app.example/consumer/bookings'))
            ->withHeader('Referer', 'https://app.example/consumer/bookings?week_start=2026-04-19');

        $response = $middleware->process($request, $this->invalidCsrfHandler());

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/consumer/bookings?week_start=2026-04-19', $response->getHeaderLine('Location'));
    }

    private function invalidCsrfHandler(): RequestHandlerInterface
    {
        return new class implements RequestHandlerInterface {
            public function handle(\Psr\Http\Message\ServerRequestInterface $request): ResponseInterface
            {
                throw new InvalidCsrfTokenException('Bad CSRF token.');
            }
        };
    }
}
