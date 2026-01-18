<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use App\Context\AdminContext;
use App\Context\RequestContext;
use App\Domain\Contracts\AdminTotpSecretStoreInterface;
use App\Domain\Enum\Scope;
use App\Domain\Enum\SessionState;
use App\Domain\Service\StepUpService;
use App\Http\Middleware\SessionStateGuardMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Interfaces\RouteInterface;
use Slim\Interfaces\RouteParserInterface;
use Slim\Routing\RouteContext;
use Slim\Routing\RoutingResults;

class SessionStateGuardMiddlewareTest extends TestCase
{
    private MockObject|StepUpService $stepUpService;
    private MockObject|AdminTotpSecretStoreInterface $totpSecretStore;
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

    public function testUnauthorizedIfNoAdminContext(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);

        $request->method('getAttribute')->with(AdminContext::class)->willReturn(null);

        $response = $this->middleware->process($request, $handler);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertJsonStringEqualsJsonString(
            json_encode(['error' => 'Authentication required']),
            (string) $response->getBody()
        );
    }

    public function testUnauthorizedIfNoSessionId(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);

        $adminContext = new AdminContext(1);
        $request->expects($this->any())->method('getAttribute')
            ->willReturnMap([
                [AdminContext::class, null, $adminContext],
            ]);

        $uri = $this->createMock(UriInterface::class);
        $uri->method('getPath')->willReturn('/dashboard');
        $request->method('getUri')->willReturn($uri);

        $request->method('getCookieParams')->willReturn([]); // No auth_token

        $response = $this->middleware->process($request, $handler);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['error' => 'Session required']),
            (string) $response->getBody()
        );
    }

    public function testSkippedForAllowlistedRoutes(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);

        $adminContext = new AdminContext(1);

        // Mock Route dependencies
        $route = $this->createMock(RouteInterface::class);
        $route->method('getName')->willReturn('2fa.verify');

        $routeParser = $this->createMock(RouteParserInterface::class);
        $routingResults = $this->createMock(RoutingResults::class);

        $request->expects($this->any())->method('getAttribute')
            ->willReturnMap([
                [AdminContext::class, null, $adminContext],
                [RouteContext::ROUTE, null, $route],
                [RouteContext::ROUTE_PARSER, null, $routeParser],
                [RouteContext::ROUTING_RESULTS, null, $routingResults],
                [RouteContext::BASE_PATH, null, null],
            ]);

        $uri = $this->createMock(UriInterface::class);
        $uri->method('getPath')->willReturn('/2fa/verify');
        $request->method('getUri')->willReturn($uri);

        $request->method('getCookieParams')->willReturn(['auth_token' => 'session123']);

        // Handler should be called
        $responseMock = $this->createMock(ResponseInterface::class);
        $handler->expects($this->once())->method('handle')->willReturn($responseMock);

        $this->middleware->process($request, $handler);
    }

    public function testActiveSessionProceeds(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $context = new RequestContext('req1', '127.0.0.1', 'ua');

        $adminContext = new AdminContext(1);

        // Mock Route dependencies
        $route = null; // No route matched or just not set, but middleware handles null route gracefully?
        // Wait, middleware code:
        // $route = $routeContext->getRoute();
        // $routeName = $route ? $route->getName() : null;

        // So we can return null for route, but MUST return parser/results for RouteContext creation.
        $routeParser = $this->createMock(RouteParserInterface::class);
        $routingResults = $this->createMock(RoutingResults::class);

        $request->expects($this->any())->method('getAttribute')
            ->willReturnMap([
                [AdminContext::class, null, $adminContext],
                [RequestContext::class, null, $context],
                [RouteContext::ROUTE, null, null],
                [RouteContext::ROUTE_PARSER, null, $routeParser],
                [RouteContext::ROUTING_RESULTS, null, $routingResults],
                [RouteContext::BASE_PATH, null, null],
            ]);

        $uri = $this->createMock(UriInterface::class);
        $uri->method('getPath')->willReturn('/dashboard');
        $request->method('getUri')->willReturn($uri);

        $request->method('getCookieParams')->willReturn(['auth_token' => 'session123']);

        $this->stepUpService->method('getSessionState')
            ->with(1, 'session123', $context)
            ->willReturn(SessionState::ACTIVE);

        $responseMock = $this->createMock(ResponseInterface::class);
        $handler->expects($this->once())->method('handle')->willReturn($responseMock);

        $this->middleware->process($request, $handler);
    }

    public function testPendingStepUpApiReturns403(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $context = new RequestContext('req1', '127.0.0.1', 'ua');

        $adminContext = new AdminContext(1);

        $routeParser = $this->createMock(RouteParserInterface::class);
        $routingResults = $this->createMock(RoutingResults::class);

        $request->expects($this->any())->method('getAttribute')
            ->willReturnMap([
                [AdminContext::class, null, $adminContext],
                [RequestContext::class, null, $context],
                [RouteContext::ROUTE, null, null],
                [RouteContext::ROUTE_PARSER, null, $routeParser],
                [RouteContext::ROUTING_RESULTS, null, $routingResults],
                [RouteContext::BASE_PATH, null, null],
            ]);

        $uri = $this->createMock(UriInterface::class);
        $uri->method('getPath')->willReturn('/api/resource'); // API
        $request->method('getUri')->willReturn($uri);

        $request->method('getCookieParams')->willReturn(['auth_token' => 'session123']);

        $this->stepUpService->method('getSessionState')
            ->willReturn(SessionState::PENDING_STEP_UP);

        $this->stepUpService->expects($this->once())->method('logDenial')
            ->with(1, 'session123', Scope::LOGIN, $context);

        $response = $this->middleware->process($request, $handler);

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
        $body = json_decode((string)$response->getBody(), true);
        $this->assertEquals('STEP_UP_REQUIRED', $body['code']);
        $this->assertEquals('login', $body['scope']);
    }

    public function testPendingStepUpWebRedirectsToVerify(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $context = new RequestContext('req1', '127.0.0.1', 'ua');

        $adminContext = new AdminContext(1);

        $routeParser = $this->createMock(RouteParserInterface::class);
        $routingResults = $this->createMock(RoutingResults::class);

        $request->expects($this->any())->method('getAttribute')
            ->willReturnMap([
                [AdminContext::class, null, $adminContext],
                [RequestContext::class, null, $context],
                [RouteContext::ROUTE, null, null],
                [RouteContext::ROUTE_PARSER, null, $routeParser],
                [RouteContext::ROUTING_RESULTS, null, $routingResults],
                [RouteContext::BASE_PATH, null, null],
            ]);

        $uri = $this->createMock(UriInterface::class);
        $uri->method('getPath')->willReturn('/dashboard'); // Web
        $request->method('getUri')->willReturn($uri);

        $request->method('getCookieParams')->willReturn(['auth_token' => 'session123']);

        $this->stepUpService->method('getSessionState')
            ->willReturn(SessionState::PENDING_STEP_UP);

        $this->totpSecretStore->method('exists')->with(1)->willReturn(true);

        $response = $this->middleware->process($request, $handler);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/2fa/verify', $response->getHeaderLine('Location'));
    }

    public function testPendingStepUpWebRedirectsToSetup(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $context = new RequestContext('req1', '127.0.0.1', 'ua');

        $adminContext = new AdminContext(1);

        $routeParser = $this->createMock(RouteParserInterface::class);
        $routingResults = $this->createMock(RoutingResults::class);

        $request->expects($this->any())->method('getAttribute')
            ->willReturnMap([
                [AdminContext::class, null, $adminContext],
                [RequestContext::class, null, $context],
                [RouteContext::ROUTE, null, null],
                [RouteContext::ROUTE_PARSER, null, $routeParser],
                [RouteContext::ROUTING_RESULTS, null, $routingResults],
                [RouteContext::BASE_PATH, null, null],
            ]);

        $uri = $this->createMock(UriInterface::class);
        $uri->method('getPath')->willReturn('/dashboard'); // Web
        $request->method('getUri')->willReturn($uri);

        $request->method('getCookieParams')->willReturn(['auth_token' => 'session123']);

        $this->stepUpService->method('getSessionState')
            ->willReturn(SessionState::PENDING_STEP_UP);

        $this->totpSecretStore->method('exists')->with(1)->willReturn(false); // No secret

        $response = $this->middleware->process($request, $handler);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/2fa/setup', $response->getHeaderLine('Location'));
    }
}
