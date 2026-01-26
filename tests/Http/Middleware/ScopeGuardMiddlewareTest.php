<?php

declare(strict_types=1);

namespace Tests\Http\Middleware;

use App\Context\AdminContext;
use App\Context\RequestContext;
use App\Domain\Enum\Scope;
use App\Domain\Service\StepUpService;
use App\Http\Middleware\ScopeGuardMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Interfaces\RouteParserInterface;
use Slim\Psr7\Response;
use Slim\Routing\Route;
use Slim\Routing\RouteContext;
use Slim\Routing\RoutingResults;

class ScopeGuardMiddlewareTest extends TestCase
{
    private StepUpService&MockObject $stepUpService;
    private ScopeGuardMiddleware $middleware;

    protected function setUp(): void
    {
        $this->stepUpService = $this->createMock(StepUpService::class);
        $this->middleware = new ScopeGuardMiddleware($this->stepUpService);
    }

    public function testDeniesAccessWhenNoAdminId(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        // Expect AdminContext::class and return null
        $request->method('getAttribute')->with(AdminContext::class)->willReturn(null);
        $handler = $this->createMock(RequestHandlerInterface::class);

        $response = $this->middleware->process($request, $handler);
        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testAllowsAccessWithValidGrant(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getCookieParams')->willReturn(['auth_token' => 'token123']);

        // Mock a route requiring ADMIN_CREATE scope (was SECURITY in old comment)
        $route = $this->createMock(Route::class);
        $route->method('getName')->willReturn('admin.create'); // Mapped to ADMIN_CREATE in Registry

        $routeParser = $this->createMock(RouteParserInterface::class);
        $routingResults = $this->createMock(RoutingResults::class);

        $request->method('getAttribute')->willReturnMap([
            [AdminContext::class, null, new AdminContext(123)],
            [RouteContext::ROUTE, null, $route],
            [RouteContext::ROUTE_PARSER, null, $routeParser],
            [RouteContext::ROUTING_RESULTS, null, $routingResults],
            [RequestContext::class, null, new RequestContext('req-123', '127.0.0.1', 'phpunit')]
        ]);

        // Expect hasGrant call for ADMIN_CREATE scope
        // Note: middleware checks session state first. We need to mock getSessionState returns ACTIVE
        $this->stepUpService->expects($this->once())
            ->method('getSessionState')
            ->willReturn(\App\Domain\Enum\SessionState::ACTIVE);

        $this->stepUpService->expects($this->once())
            ->method('hasGrant')
            ->with(123, 'token123', Scope::ADMIN_CREATE)
            ->willReturn(true);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->willReturn(new \Slim\Psr7\Response());

        $this->middleware->process($request, $handler);
    }
}
