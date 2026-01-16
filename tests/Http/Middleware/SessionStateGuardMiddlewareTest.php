<?php

declare(strict_types=1);

namespace Tests\Http\Middleware;

use App\Context\AdminContext;
use App\Context\RequestContext;
use App\Domain\Contracts\TotpSecretRepositoryInterface;
use App\Domain\Enum\SessionState;
use App\Domain\Service\StepUpService;
use App\Http\Middleware\SessionStateGuardMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Interfaces\RouteParserInterface;
use Slim\Routing\Route;
use Slim\Routing\RouteContext;
use Slim\Routing\RoutingResults;

class SessionStateGuardMiddlewareTest extends TestCase
{
    private StepUpService $stepUpService;
    private TotpSecretRepositoryInterface $totpSecretRepository;
    private SessionStateGuardMiddleware $middleware;

    protected function setUp(): void
    {
        $this->stepUpService = $this->createMock(StepUpService::class);
        $this->totpSecretRepository = $this->createMock(TotpSecretRepositoryInterface::class);
        $this->middleware = new SessionStateGuardMiddleware(
            $this->stepUpService,
            $this->totpSecretRepository
        );
    }

    public function testDeniesAccessWhenStateIsNotActive(): void
    {
        $uri = $this->createMock(UriInterface::class);
        $uri->method('getPath')->willReturn('/api/protected');

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getUri')->willReturn($uri);
        $request->method('getCookieParams')->willReturn(['auth_token' => 'token123']);

        // Mock RouteContext to return a route that is NOT stepup verify
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

        $response = $this->middleware->process($request, $handler);
        $this->assertEquals(403, $response->getStatusCode());

        $body = json_decode((string)$response->getBody(), true);
        $this->assertEquals('STEP_UP_REQUIRED', $body['code']);
        $this->assertEquals('login', $body['scope']);
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
        $handler->expects($this->once())->method('handle')->willReturn(new \Slim\Psr7\Response());

        $this->middleware->process($request, $handler);
    }
}
