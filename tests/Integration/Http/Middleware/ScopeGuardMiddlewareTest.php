<?php

declare(strict_types=1);

namespace Tests\Integration\Http\Middleware;

use Maatify\AdminKernel\Context\AdminContext;
use Maatify\AdminKernel\Context\RequestContext;
use Maatify\AdminKernel\Domain\Enum\Scope;
use Maatify\AdminKernel\Domain\Enum\SessionState;
use Maatify\AdminKernel\Domain\Security\RedirectToken\RedirectTokenServiceInterface;
use Maatify\AdminKernel\Domain\Service\StepUpService;
use Maatify\AdminKernel\Http\Middleware\ScopeGuardMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Interfaces\RouteInterface;
use Slim\Interfaces\RouteParserInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Routing\RouteContext;
use Slim\Routing\RoutingResults;

class ScopeGuardMiddlewareTest extends TestCase
{
    private StepUpService&MockObject $stepUpService;
    private RedirectTokenServiceInterface&MockObject $redirectTokenService;
    private ScopeGuardMiddleware $middleware;

    protected function setUp(): void
    {
        $this->stepUpService = $this->createMock(StepUpService::class);
        $this->redirectTokenService = $this->createMock(RedirectTokenServiceInterface::class);
        $this->middleware = new ScopeGuardMiddleware($this->stepUpService, $this->redirectTokenService);
    }

    private function createRouteContext(string $routeName): RouteContext
    {
        $route = $this->createMock(RouteInterface::class);
        $route->method('getName')->willReturn($routeName);

        $routeParser = $this->createMock(RouteParserInterface::class);
        $routingResults = $this->createMock(RoutingResults::class);

        $reflector = new \ReflectionClass(RouteContext::class);
        $routeContext = $reflector->newInstanceWithoutConstructor();
        $constructor = $reflector->getConstructor();
        $constructor->setAccessible(true);
        // Slim 4 signature varies, using nullable first arg as per previous tests
        $constructor->invoke($routeContext, $route, $routeParser, $routingResults);

        return $routeContext;
    }

    public function testRedirectsToVerifyWithSignedTokenOnWeb(): void
    {
        // Setup Request: GET /admin/protected
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/admin/protected?id=1');

        $request = $request
            ->withAttribute(AdminContext::class, new AdminContext(1))
            ->withAttribute(RequestContext::class, new RequestContext('id', '1.1.1.1', 'ua'))
            ->withCookieParams(['auth_token' => 'sess'])
            // Use RouteContext::class as per successful test pattern in SessionStateGuardMiddlewareTest
            ->withAttribute(RouteContext::class, $this->createRouteContext('admins.create'));

        // StepUpService checks
        $this->stepUpService->method('getSessionState')->willReturn(SessionState::ACTIVE);
        // Grant check fails -> Redirect needed
        $this->stepUpService->expects($this->once())
            ->method('hasGrant')
            ->willReturn(false);

        // Redirect Token Service expects sign call
        $this->redirectTokenService->expects($this->once())
            ->method('create')
            ->with('/admin/protected?id=1')
            ->willReturn('signed.token.123');

        $handler = $this->createMock(RequestHandlerInterface::class);

        $response = $this->middleware->process($request, $handler);

        // Debug output to see what we actually got
        if ($response->getStatusCode() === 0) {
             // If status is 0, it means we likely got a mock response from somewhere or failed logic.
             // The only place returning 0 is if we returned $handler->handle($request) and handler returned an unconfigured mock response.
             // But we mocked StepUpService::hasGrant to return false, so we EXPECT a redirect.
             // If we entered the "catch" block for RouteContext, we returned $handler->handle().
             // We need to ensure we DO NOT trigger the catch block.
             // The previous fix used `RouteContext::class` attribute, which should work if `RouteContext::fromRequest` looks for it.
             // Slim 4 `RouteContext::fromRequest` looks for `RouteContext::ROUTE_CONTEXT`.
        }

        $this->assertSame(302, $response->getStatusCode());
        // Verify Location header format: /2fa/verify?scope=...&r=...
        $location = $response->getHeaderLine('Location');
        $this->assertStringContainsString('/2fa/verify', $location);
        $this->assertStringContainsString('r=signed.token.123', $location);
        $this->assertStringContainsString('scope=admins.create', $location);
    }
}
