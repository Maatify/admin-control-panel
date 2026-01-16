<?php

declare(strict_types=1);

namespace Tests\Http\Middleware;

use App\Context\RequestContext;
use App\Http\Middleware\RequestContextMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;

class RequestContextMiddlewareTest extends TestCase
{
    public function testItCreatesRequestContext(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $uri = $this->createMock(UriInterface::class);
        $uri->method('getPath')->willReturn('/test');

        $request->method('getAttribute')->willReturnMap([
            ['request_id', null, '123-abc'],
            ['__route__', null, null],
            ['route', null, null],
        ]);

        $request->method('getMethod')->willReturn('GET');
        $request->method('getUri')->willReturn($uri);

        $request->expects($this->once())
            ->method('getServerParams')
            ->willReturn(['REMOTE_ADDR' => '127.0.0.1', 'HTTP_USER_AGENT' => 'TestAgent']);

        $request->expects($this->once())
            ->method('withAttribute')
            ->with(RequestContext::class, $this->callback(function (RequestContext $context) {
                return $context->requestId === '123-abc'
                    && $context->ipAddress === '127.0.0.1'
                    && $context->userAgent === 'TestAgent';
            }))
            ->willReturnSelf();

        $handler->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willReturn($response);

        $middleware = new RequestContextMiddleware();
        $middleware->process($request, $handler);
    }

    public function testItFailsWithoutRequestId(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('RequestContextMiddleware called without valid request_id. Ensure RequestIdMiddleware runs before RequestContextMiddleware.');

        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);

        $request->expects($this->once())
            ->method('getAttribute')
            ->with('request_id')
            ->willReturn(null);

        $middleware = new RequestContextMiddleware();
        $middleware->process($request, $handler);
    }
}
