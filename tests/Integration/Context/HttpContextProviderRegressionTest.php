<?php

declare(strict_types=1);

namespace Tests\Integration\Context;

use App\Context\HttpContextProvider;
use App\Context\Resolver\AdminContextResolver;
use App\Context\Resolver\RequestContextResolver;
use App\Domain\Service\SessionValidationService;
use App\Http\Middleware\ContextProviderMiddleware;
use App\Http\Middleware\SessionGuardMiddleware;
use DI\Container;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;

class HttpContextProviderRegressionTest extends TestCase
{
    public function test_it_receives_updated_request_after_session_guard_sets_admin_id(): void
    {
        // 1. Setup DI Container
        $container = new Container();

        // 2. Mock SessionValidationService to simulate valid session
        $sessionValidationService = $this->createMock(SessionValidationService::class);
        $sessionValidationService->method('validate')
            ->willReturn(123); // Always return adminId 123

        // 3. Register HttpContextProvider in Container
        // This mirrors existing app definition where dependencies are injected
        $container->set(HttpContextProvider::class, function (Container $c) {
            return new HttpContextProvider(
                $c->get(ServerRequestInterface::class), // Crucial: This must resolve to the *updated* request
                new AdminContextResolver(),
                new RequestContextResolver()
            );
        });

        // 4. Instantiate Middlewares
        $contextMiddleware = new ContextProviderMiddleware($container);
        $sessionGuardMiddleware = new SessionGuardMiddleware($sessionValidationService, $container);

        // 5. Create Initial Request (Simulate RequestIdMiddleware having run)
        $request = ServerRequestFactory::createFromGlobals();
        $request = $request->withAttribute('request_id', 'req_test_123');
        $request = $request->withCookieParams(['auth_token' => 'valid_token']);

        // 6. Define the handler that runs AFTER middleware
        // This handler will resolve HttpContextProvider to verify what it sees
        $finalHandler = new class($container) implements RequestHandlerInterface {
            public function __construct(private Container $container) {}

            public function handle(ServerRequestInterface $request): ResponseInterface {
                // Resolve HttpContextProvider from container (simulating Controller injection)
                $provider = $this->container->get(HttpContextProvider::class);

                // We'll perform assertions here, or return the provider to the test for assertions
                // Returning it via response body is messy, so we'll just store it or assert here.
                // However, assertions inside anonymous class are hard to bubble up.
                // Better approach: The test scope has access to $container.
                // Since the container is passed by reference/object, checking it AFTER execution is fine
                // IF HttpContextProvider is resolved dynamically.
                // BUT HttpContextProvider is scoped to request.
                // The issue is that we need to ensure the HttpContextProvider *instantiated* inside the
                // application flow gets the right request.

                // Let's resolve it here and check.
                return new Response();
            }
        };

        // 7. Chain Middlewares: ContextProvider -> SessionGuard -> Handler
        // We use a helper to chain them properly
        $handler = new class($sessionGuardMiddleware, $finalHandler) implements RequestHandlerInterface {
            public function __construct(
                private SessionGuardMiddleware $middleware,
                private RequestHandlerInterface $next
            ) {}
            public function handle(ServerRequestInterface $request): ResponseInterface {
                return $this->middleware->process($request, $this->next);
            }
        };

        // Execute Pipeline
        // ContextProviderMiddleware runs first, sets initial request
        $contextMiddleware->process($request, $handler);

        // 8. Assertions
        // Now that the pipeline has run, we resolve HttpContextProvider from the container.
        // Since SessionGuardMiddleware updated the container, we should get the new request.

        /** @var HttpContextProvider $provider */
        $provider = $container->get(HttpContextProvider::class);

        // Assert Request Context
        $this->assertNotNull($provider->request(), 'Request context should not be null');
        $this->assertEquals('req_test_123', $provider->request()->requestId);

        // Assert Admin Context (The Core Regression Test)
        $this->assertNotNull($provider->admin(), 'Admin context should not be null (indicates admin_id missing)');
        $this->assertEquals(123, $provider->admin()->adminId, 'Admin ID should match the one set by SessionGuard');
    }
}
