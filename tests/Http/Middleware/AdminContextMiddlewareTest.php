<?php

declare(strict_types=1);

namespace Tests\Http\Middleware;

use App\Context\AdminContext;
use App\Domain\Contracts\AdminSessionRepositoryInterface;
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

        $request->expects($this->any())
            ->method('getAttribute')
            ->willReturnCallback(function (string $name) {
                if ($name === 'admin_id') {
                    return 101;
                }
                return null;
            });

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

        $sessionRepo = $this->createMock(AdminSessionRepositoryInterface::class);
        $middleware = new AdminContextMiddleware($sessionRepo);
        $middleware->process($request, $handler);
    }

    public function testItDoesNothingWhenAdminIdMissing(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $request->expects($this->any())
            ->method('getAttribute')
            ->willReturnCallback(function (string $name) {
                if ($name === 'admin_id') {
                    return null;
                }
                return null;
            });

        $request->expects($this->never())
            ->method('withAttribute');

        $handler->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willReturn($response);

        $sessionRepo = $this->createMock(AdminSessionRepositoryInterface::class);
        $middleware = new AdminContextMiddleware($sessionRepo);
        $middleware->process($request, $handler);
    }
}
