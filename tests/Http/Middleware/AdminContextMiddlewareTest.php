<?php

declare(strict_types=1);

namespace Tests\Http\Middleware;

use App\Context\AdminContext;
use App\Http\Middleware\AdminContextMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;

class AdminContextMiddlewareTest extends TestCase
{
    public function testItCreatesAdminContextWhenIdPresent(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $request->expects($this->once())
            ->method('getAttribute')
            ->with('admin_id')
            ->willReturn(101);

        $request->expects($this->once())
            ->method('withAttribute')
            ->with(AdminContext::class, $this->callback(function (AdminContext $context) {
                return $context->adminId === 101;
            }))
            ->willReturnSelf();

        $handler->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willReturn($response);

        $middleware = new AdminContextMiddleware();
        $middleware->process($request, $handler);
    }

    public function testItDoesNothingWhenAdminIdMissing(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $request->expects($this->once())
            ->method('getAttribute')
            ->with('admin_id')
            ->willReturn(null);

        $request->expects($this->never())
            ->method('withAttribute');

        $handler->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willReturn($response);

        $middleware = new AdminContextMiddleware();
        $middleware->process($request, $handler);
    }
}
