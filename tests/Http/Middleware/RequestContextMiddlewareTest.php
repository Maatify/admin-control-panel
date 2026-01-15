<?php

declare(strict_types=1);

namespace Tests\Http\Middleware;

use App\Context\RequestContext;
use App\Http\Middleware\RequestContextMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;

final class RequestContextMiddlewareTest extends TestCase
{
    public function testItCreatesRequestContext(): void
    {
        $request  = $this->createMock(ServerRequestInterface::class);
        $handler  = $this->createMock(RequestHandlerInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        // Allow multiple getAttribute() calls with different keys
        $request->method('getAttribute')
            ->willReturnCallback(static function (string $name, mixed $default = null) {
                return match ($name) {
                    'request_id' => '123-abc',
                    '__route__'  => null, // route may or may not exist
                    default      => $default,
                };
            });

        $request->expects($this->once())
            ->method('getServerParams')
            ->willReturn([
                'REMOTE_ADDR'     => '127.0.0.1',
                'HTTP_USER_AGENT' => 'TestAgent',
            ]);

        $request->expects($this->once())
            ->method('withAttribute')
            ->with(
                RequestContext::class,
                $this->callback(static function (RequestContext $context): bool {
                    return $context->requestId === '123-abc'
                           && $context->ipAddress === '127.0.0.1'
                           && $context->userAgent === 'TestAgent';
                })
            )
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
        $this->expectExceptionMessage(
            'RequestContextMiddleware called without valid request_id. Ensure RequestIdMiddleware runs before RequestContextMiddleware.'
        );

        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);

        $request->method('getAttribute')
            ->willReturnCallback(static function (string $name, mixed $default = null) {
                return $name === 'request_id' ? null : $default;
            });

        $middleware = new RequestContextMiddleware();
        $middleware->process($request, $handler);
    }
}
