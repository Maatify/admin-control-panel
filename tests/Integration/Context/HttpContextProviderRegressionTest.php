<?php

declare(strict_types=1);

namespace Tests\Integration\Context;

use App\Context\AdminContext;
use App\Context\RequestContext;
use App\Domain\Contracts\AdminSessionRepositoryInterface;
use App\Domain\Service\SessionValidationService;
use App\Http\Middleware\AdminContextMiddleware;
use App\Http\Middleware\SessionGuardMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;

final class HttpContextProviderRegressionTest extends TestCase
{
    public function test_admin_context_is_attached_after_session_guard_sets_admin_id(): void
    {
        // Arrange: mock valid session -> adminId 123
        $sessionValidationService = $this->createMock(SessionValidationService::class);
        $sessionValidationService->method('validate')->willReturn(123);

        $sessionGuard = new SessionGuardMiddleware($sessionValidationService);
        $sessionRepo = $this->createMock(AdminSessionRepositoryInterface::class);
        $adminContextMw = new AdminContextMiddleware($sessionRepo);

        // Build request with required prerequisites
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/any-protected-route');
        $request = $request->withCookieParams(['auth_token' => 'valid_token']);

        // Provide RequestContext attribute (normally produced by RequestIdMiddleware + RequestContextMiddleware)
        $requestContext = new RequestContext(
            requestId: 'req_test_123',
            ipAddress: '127.0.0.1',
            userAgent: 'phpunit'
        );
        $request = $request->withAttribute(RequestContext::class, $requestContext);

        // Handler after AdminContextMiddleware to assert final state
        $finalHandler = new class() implements RequestHandlerInterface {
            public ?ServerRequestInterface $seenRequest = null;

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->seenRequest = $request;
                return new Response();
            }
        };

        // Chain: SessionGuard -> AdminContextMiddleware -> FinalHandler
        $handlerAfterSession = new class($adminContextMw, $finalHandler) implements RequestHandlerInterface {
            public function __construct(
                private AdminContextMiddleware $mw,
                private RequestHandlerInterface $next
            ) {}

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->mw->process($request, $this->next);
            }
        };

        // Act
        $sessionGuard->process($request, $handlerAfterSession);

        // Assert
        self::assertInstanceOf(ServerRequestInterface::class, $finalHandler->seenRequest);

        $seen = $finalHandler->seenRequest;

        $rc = $seen->getAttribute(RequestContext::class);
        self::assertInstanceOf(RequestContext::class, $rc);
        self::assertSame('req_test_123', $rc->requestId);

        $ac = $seen->getAttribute(AdminContext::class);
        self::assertInstanceOf(AdminContext::class, $ac);
        self::assertSame(123, $ac->adminId);
    }
}
