<?php

declare(strict_types=1);

namespace Tests\Http\Middleware;

use App\Domain\Enum\Scope;
use App\Domain\Service\StepUpService;
use App\Http\Middleware\ScopeGuardMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;
use Slim\Routing\Route;
use Slim\Routing\RouteContext;

class ScopeGuardMiddlewareTest extends TestCase
{
    private StepUpService $stepUpService;
    private ScopeGuardMiddleware $middleware;

    protected function setUp(): void
    {
        $this->stepUpService = $this->createMock(StepUpService::class);
        $this->middleware = new ScopeGuardMiddleware($this->stepUpService);
    }

    public function testDeniesAccessWhenNoAdminId(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')->with('admin_id')->willReturn(null);
        $handler = $this->createMock(RequestHandlerInterface::class);

        $response = $this->middleware->process($request, $handler);
        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testAllowsAccessWithValidGrant(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')->with('admin_id')->willReturn(123);
        $request->method('getHeaderLine')->with('Authorization')->willReturn('Bearer token123');

        // Mock a route requiring SECURITY scope
        $route = $this->createMock(Route::class);
        $route->method('getName')->willReturn('admin.create'); // Mapped to SECURITY in Registry

        $request->method('getAttribute')->will($this->returnValueMap([
            ['admin_id', null, 123],
            [RouteContext::ROUTE, null, $route]
        ]));

        // Expect hasGrant call for SECURITY scope
        $this->stepUpService->expects($this->once())
            ->method('hasGrant')
            ->with(123, 'token123', Scope::SECURITY)
            ->willReturn(true);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->willReturn(new \Slim\Psr7\Response());

        $this->middleware->process($request, $handler);
    }
}
