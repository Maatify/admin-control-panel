<?php

declare(strict_types=1);

namespace Tests\Integration\Http\Auth;

use Maatify\AdminKernel\Context\AdminContext;
use Maatify\AdminKernel\Context\RequestContext;
use Maatify\AdminKernel\Domain\Contracts\Admin\AdminTotpSecretStoreInterface;
use Maatify\AdminKernel\Domain\Enum\SessionState;
use Maatify\AdminKernel\Domain\Security\RedirectToken\RedirectTokenServiceInterface;
use Maatify\AdminKernel\Domain\Service\StepUpService;
use Maatify\AdminKernel\Http\Middleware\SessionStateGuardMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Interfaces\RouteParserInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Routing\RouteContext;
use Slim\Routing\RoutingResults;

class SessionStateGuardMiddlewareTest extends TestCase
{
    private StepUpService&MockObject $stepUpService;
    private AdminTotpSecretStoreInterface&MockObject $totpSecretStore;
    private RedirectTokenServiceInterface&MockObject $redirectTokenService;
    private SessionStateGuardMiddleware $middleware;

    protected function setUp(): void
    {
        $this->stepUpService = $this->createMock(StepUpService::class);
        $this->totpSecretStore = $this->createMock(AdminTotpSecretStoreInterface::class);
        $this->redirectTokenService = $this->createMock(RedirectTokenServiceInterface::class);

        $this->middleware = new SessionStateGuardMiddleware(
            $this->stepUpService,
            $this->totpSecretStore,
            $this->redirectTokenService
        );
    }

    private function createRouteContext(): RouteContext
    {
        $routeParser = $this->createMock(RouteParserInterface::class);
        $routingResults = $this->createMock(RoutingResults::class);

        $reflector = new \ReflectionClass(RouteContext::class);
        $routeContext = $reflector->newInstanceWithoutConstructor();
        $constructor = $reflector->getConstructor();
        $constructor->setAccessible(true);
        // __construct(?RouteInterface $route, RouteParserInterface $routeParser, RoutingResults $routingResults, ?string $basePath = null)
        $constructor->invoke($routeContext, null, $routeParser, $routingResults);

        return $routeContext;
    }

    public function testRedirectsToVerifyWithTokenWhenSessionInactive(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/protected/resource?filter=on');

        $request = $request
            ->withAttribute(AdminContext::class, new AdminContext(1))
            ->withAttribute(RequestContext::class, new RequestContext('id', '127.0.0.1', 'ua'))
            // Slim 4 RouteContext::ROUTE_CONTEXT value
            ->withAttribute(RouteContext::class, $this->createRouteContext())
            ->withCookieParams(['auth_token' => 'valid-session']);

        $handler = $this->createMock(RequestHandlerInterface::class);

        // Expect state check: PENDING_STEP_UP
        $this->stepUpService->expects($this->once())
            ->method('getSessionState')
            ->willReturn(SessionState::PENDING_STEP_UP);

        // Expect secret check: Exists (so redirect to verify, not setup)
        $this->totpSecretStore->expects($this->once())
            ->method('exists')
            ->with(1)
            ->willReturn(true);

        // Expect token generation for FULL path + query
        $this->redirectTokenService->expects($this->once())
            ->method('create')
            ->with('/protected/resource?filter=on')
            ->willReturn('secure.token');

        $response = $this->middleware->process($request, $handler);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/2fa/verify?r=secure.token', $response->getHeaderLine('Location'));
    }

    public function testRedirectsToSetupWithTokenWhenSecretMissing(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/settings/security');

        $request = $request
            ->withAttribute(AdminContext::class, new AdminContext(1))
            ->withAttribute(RequestContext::class, new RequestContext('id', '127.0.0.1', 'ua'))
            ->withAttribute(RouteContext::class, $this->createRouteContext())
            ->withCookieParams(['auth_token' => 'valid-session']);

        $handler = $this->createMock(RequestHandlerInterface::class);

        $this->stepUpService->method('getSessionState')->willReturn(SessionState::PENDING_STEP_UP);

        $this->totpSecretStore->method('exists')->willReturn(false);

        $this->redirectTokenService->expects($this->once())
            ->method('create')
            ->with('/settings/security')
            ->willReturn('setup.token');

        $response = $this->middleware->process($request, $handler);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/2fa/setup?r=setup.token', $response->getHeaderLine('Location'));
    }
}
