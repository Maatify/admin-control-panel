<?php

declare(strict_types=1);

namespace Tests\Http\Middleware;

use App\Context\AdminContext;
use App\Context\RequestContext;
use App\Domain\Enum\Scope;
use App\Domain\Enum\SessionState;
use App\Http\Middleware\ScopeGuardMiddleware;
use App\Domain\Service\StepUpService;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;

final class ScopeGuardMiddlewareTest extends TestCase
{
    private StepUpService $stepUpService;
    private ScopeGuardMiddleware $middleware;

    protected function setUp(): void
    {
        $this->stepUpService = $this->createMock(StepUpService::class);
        $this->middleware   = new ScopeGuardMiddleware($this->stepUpService);
    }

    public function testDeniesAccessWhenNoAdminContext(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);

        $request->expects($this->any())
            ->method('getAttribute')
            ->willReturnCallback(static function (string $name, mixed $default = null) {
                return match ($name) {
                    'request_id' => '123-abc',
                    '__route__'  => null,
                    default      => $default,
                };
            });


        $response = $this->middleware->process($request, $handler);

        $this->assertSame(401, $response->getStatusCode());
    }

    public function testAllowsAccessWithValidGrant(): void
    {
        // =========================
        // Arrange
        // =========================

        $stepUpService = $this->createMock(StepUpService::class);

        $stepUpService->expects($this->once())
            ->method('getSessionState')
            ->willReturn(SessionState::ACTIVE);

        $stepUpService->expects($this->once())
            ->method('hasGrant')
            ->with(
                123,
                'session123',
                Scope::SECURITY,
                $this->isInstanceOf(RequestContext::class)
            )
            ->willReturn(true);

        $app = AppFactory::create();

        // 2️⃣ then your middleware
        $app->add(new ScopeGuardMiddleware($stepUpService));

        // 1️⃣ routing FIRST
        $app->addRoutingMiddleware();

        // 3️⃣ routes
        $app->get('/admins', function () {
            return new Response();
        })->setName('admins.create');

        // =========================
        // Build real request
        // =========================

        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/admins');

        // Inject required context attributes
        $request = $request
            ->withAttribute(AdminContext::class, new AdminContext(123))
            ->withAttribute(
                RequestContext::class,
                new RequestContext(
                    requestId: 'req-123',
                    ipAddress: '127.0.0.1',
                    userAgent: 'PHPUnit',
                    routeName: 'admins.create',
                    method: 'GET',
                    path: '/admins'
                )
            )
            ->withCookieParams([
                'auth_token' => 'session123',
            ]);

        // =========================
        // Act
        // =========================

        $response = $app->handle($request);

        // =========================
        // Assert
        // =========================

        $this->assertSame(200, $response->getStatusCode());
    }
}