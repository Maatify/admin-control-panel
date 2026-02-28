<?php

declare(strict_types=1);

namespace Tests\Http\Middleware;

use Maatify\AdminKernel\Context\AdminContext;
use Maatify\AdminKernel\Context\RequestContext;
use Maatify\AdminKernel\Domain\Contracts\Admin\AdminTotpSecretStoreInterface;
use Maatify\AdminKernel\Domain\Enum\SessionState;
use Maatify\AdminKernel\Domain\Exception\StepUpRequiredException;
use Maatify\AdminKernel\Domain\Service\StepUpService;
use Maatify\AdminKernel\Http\Middleware\SessionStateGuardMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Interfaces\RouteParserInterface;
use Slim\Routing\Route;
use Slim\Routing\RouteContext;
use Slim\Routing\RoutingResults;

class SessionStateGuardMiddlewareTest extends TestCase
{
    private StepUpService&MockObject $stepUpService;
    private AdminTotpSecretStoreInterface&MockObject $totpSecretStore;
    private SessionStateGuardMiddleware $middleware;

    protected function setUp(): void
    {
        $this->stepUpService = $this->createMock(StepUpService::class);
        $this->totpSecretStore = $this->createMock(AdminTotpSecretStoreInterface::class);
        $this->middleware = new SessionStateGuardMiddleware(
            $this->stepUpService,
            $this->totpSecretStore
        );
    }

    public function testDeniesAccessWhenStateIsNotActive(): void
    {
        $uri = $this->createMock(UriInterface::class);
        $uri->method('getPath')->willReturn('/api/protected');

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getUri')->willReturn($uri);
        $request->method('getCookieParams')->willReturn(['auth_token' => 'token123']);

        $route = $this->createMock(Route::class);
        $route->method('getName')->willReturn('some.protected.route');

        $routeParser = $this->createMock(RouteParserInterface::class);
        $routingResults = $this->createMock(RoutingResults::class);

        $request->method('getAttribute')->willReturnMap([
            [AdminContext::class, null, new AdminContext(123)],
            [RouteContext::ROUTE, null, $route],
            [RouteContext::ROUTE_PARSER, null, $routeParser],
            [RouteContext::ROUTING_RESULTS, null, $routingResults],
            [RequestContext::class, null, new RequestContext('req-123', '127.0.0.1', 'phpunit')]
        ]);

        $this->stepUpService->expects($this->once())
            ->method('getSessionState')
            ->with(123, 'token123')
            ->willReturn(SessionState::PENDING_STEP_UP);

        $handler = $this->createMock(RequestHandlerInterface::class);

        $this->expectException(\Maatify\AdminKernel\Domain\Exception\StepUpRequiredException::class);

        $this->middleware->process($request, $handler);
    }
    public function testAllowsAccessWhenStateIsActive(): void
    {
        $uri = $this->createMock(UriInterface::class);
        $uri->method('getPath')->willReturn('/api/protected');

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getUri')->willReturn($uri);
        $request->method('getCookieParams')->willReturn(['auth_token' => 'token123']);

        $route = $this->createMock(Route::class);
        $route->method('getName')->willReturn('some.protected.route');

        $routeParser = $this->createMock(RouteParserInterface::class);
        $routingResults = $this->createMock(RoutingResults::class);

        $request->method('getAttribute')->willReturnMap([
            [AdminContext::class, null, new AdminContext(123)],
            [RouteContext::ROUTE, null, $route],
            [RouteContext::ROUTE_PARSER, null, $routeParser],
            [RouteContext::ROUTING_RESULTS, null, $routingResults],
            [RequestContext::class, null, new RequestContext('req-123', '127.0.0.1', 'phpunit')]
        ]);

        $this->stepUpService->expects($this->once())
            ->method('getSessionState')
            ->with(123, 'token123')
            ->willReturn(SessionState::ACTIVE);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturn(new \Slim\Psr7\Response());

        $this->middleware->process($request, $handler);
    }
}