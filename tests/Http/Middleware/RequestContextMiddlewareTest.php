<?php

declare(strict_types=1);

namespace Tests\Http\Middleware;

use App\Context\RequestContext;
use App\Http\Middleware\RequestContextMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;

class RequestContextMiddlewareTest extends TestCase
{
    public function testItCreatesRequestContext(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $request->expects($this->once())
            ->method('getAttribute')
            ->with('request_id')
            ->willReturn('123-abc');

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
