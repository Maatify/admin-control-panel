<?php

declare(strict_types=1);

namespace Tests\Integration\Http\Auth;

use Maatify\AdminKernel\Context\RequestContext;
use Maatify\AdminKernel\Domain\Security\RedirectToken\RedirectTokenServiceInterface;
use Maatify\AdminKernel\Domain\Service\RememberMeService;
use Maatify\AdminKernel\Domain\Service\SessionValidationService;
use Maatify\AdminKernel\Http\Auth\AuthSurface;
use Maatify\AdminKernel\Http\Cookie\CookieFactoryService;
use Maatify\AdminKernel\Http\Middleware\SessionGuardMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

class SessionGuardMiddlewareTest extends TestCase
{
    private SessionValidationService&MockObject $sessionValidationService;
    private RememberMeService&MockObject $rememberMeService;
    private CookieFactoryService&MockObject $cookieFactory;
    private RedirectTokenServiceInterface&MockObject $redirectTokenService;
    private SessionGuardMiddleware $middleware;

    protected function setUp(): void
    {
        $this->sessionValidationService = $this->createMock(SessionValidationService::class);
        $this->rememberMeService = $this->createMock(RememberMeService::class);
        $this->cookieFactory = $this->createMock(CookieFactoryService::class);
        $this->redirectTokenService = $this->createMock(RedirectTokenServiceInterface::class);

        $this->middleware = new SessionGuardMiddleware(
            $this->sessionValidationService,
            $this->rememberMeService,
            $this->cookieFactory,
            $this->redirectTokenService
        );
    }

    public function testRedirectsToLoginWithTokenWhenSessionMissingInWebMode(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/dashboard');
        $request = $request->withAttribute(RequestContext::class, new RequestContext('id', '127.0.0.1', 'ua'));

        $handler = $this->createMock(RequestHandlerInterface::class);

        $this->redirectTokenService->expects($this->once())
            ->method('create')
            ->with('/dashboard')
            ->willReturn('generated.token');

        $response = $this->middleware->process($request, $handler);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/login?r=generated.token', $response->getHeaderLine('Location'));
    }

    public function testDoesNotGenerateTokenForApi(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/api/data');
        $request = $request->withAttribute(RequestContext::class, new RequestContext('id', '127.0.0.1', 'ua'));

        $handler = $this->createMock(RequestHandlerInterface::class);

        $this->redirectTokenService->expects($this->never())->method('create');

        $response = $this->middleware->process($request, $handler);

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
    }

    public function testRememberFallbackSuccessDoesNotGenerateToken(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/dashboard')
            ->withCookieParams(['remember_me' => 'valid_token']);
        $request = $request->withAttribute(RequestContext::class, new RequestContext('id', '127.0.0.1', 'ua'));

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn((new ResponseFactory())->createResponse(200));

        $this->rememberMeService->expects($this->once())
            ->method('processAutoLogin')
            ->willReturn(['session_token' => 'new_sess', 'remember_me_token' => 'new_rem']);

        $this->sessionValidationService->expects($this->once())
            ->method('validate')
            ->willReturn(1); // Admin ID

        $this->redirectTokenService->expects($this->never())->method('create');

        $response = $this->middleware->process($request, $handler);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testRememberFallbackFailureGeneratesToken(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/dashboard')
            ->withCookieParams(['remember_me' => 'invalid_token']);
        $request = $request->withAttribute(RequestContext::class, new RequestContext('id', '127.0.0.1', 'ua'));

        $handler = $this->createMock(RequestHandlerInterface::class);

        $this->rememberMeService->expects($this->once())
            ->method('processAutoLogin')
            ->willThrowException(new \Maatify\AdminKernel\Domain\Exception\InvalidCredentialsException());

        $this->redirectTokenService->expects($this->once())
            ->method('create')
            ->with('/dashboard')
            ->willReturn('generated.token');

        $response = $this->middleware->process($request, $handler);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/login?r=generated.token', $response->getHeaderLine('Location'));
    }
}
