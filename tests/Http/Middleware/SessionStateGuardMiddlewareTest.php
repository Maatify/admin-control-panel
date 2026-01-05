<?php

declare(strict_types=1);

namespace Tests\Http\Middleware;

use App\Domain\Enum\SessionState;
use App\Domain\Service\StepUpService;
use App\Http\Middleware\SessionStateGuardMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Routing\Route;
use Slim\Routing\RouteContext;

class SessionStateGuardMiddlewareTest extends TestCase
{
    private StepUpService $stepUpService;
    private SessionStateGuardMiddleware $middleware;

    protected function setUp(): void
    {
        $this->stepUpService = $this->createMock(StepUpService::class);
        $this->middleware = new SessionStateGuardMiddleware($this->stepUpService);
    }

    public function testDeniesAccessWhenStateIsNotActive(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')->with('admin_id')->willReturn(123);
        $request->method('getHeaderLine')->with('Authorization')->willReturn('Bearer token123');

        // Mock RouteContext to return a route that is NOT stepup verify
        $route = $this->createMock(Route::class);
        $route->method('getName')->willReturn('some.protected.route');
        $request->method('getAttribute')->will($this->returnValueMap([
            ['admin_id', null, 123],
            [RouteContext::ROUTE, null, $route]
        ]));

        $this->stepUpService->expects($this->once())
            ->method('getSessionState')
            ->with(123, 'token123')
            ->willReturn(SessionState::PENDING_STEP_UP);

        $handler = $this->createMock(RequestHandlerInterface::class);

        $response = $this->middleware->process($request, $handler);
        $this->assertEquals(403, $response->getStatusCode());

        $body = json_decode((string)$response->getBody(), true);
        $this->assertEquals('STEP_UP_REQUIRED', $body['code']);
        $this->assertEquals('login', $body['scope']);
    }

    public function testAllowsAccessWhenStateIsActive(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')->with('admin_id')->willReturn(123);
        $request->method('getHeaderLine')->with('Authorization')->willReturn('Bearer token123');

        $route = $this->createMock(Route::class);
        $route->method('getName')->willReturn('some.protected.route');
        $request->method('getAttribute')->will($this->returnValueMap([
            ['admin_id', null, 123],
            [RouteContext::ROUTE, null, $route]
        ]));

        $this->stepUpService->expects($this->once())
            ->method('getSessionState')
            ->with(123, 'token123')
            ->willReturn(SessionState::ACTIVE);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->willReturn(new \Slim\Psr7\Response());

        $this->middleware->process($request, $handler);
    }
}
