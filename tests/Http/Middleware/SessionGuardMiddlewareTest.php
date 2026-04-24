<?php

declare(strict_types=1);

namespace Tests\Http\Middleware;

use Maatify\AdminKernel\Context\RequestContext;
use Maatify\AdminKernel\Domain\Contracts\Auth\RedirectTokenProviderInterface;
use Maatify\AdminKernel\Domain\Service\RememberMeService;
use Maatify\AdminKernel\Domain\Service\SessionValidationService;
use Maatify\AdminKernel\Http\Cookie\CookieFactoryService;
use Maatify\AdminKernel\Http\Middleware\SessionGuardMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;

final class SessionGuardMiddlewareTest extends TestCase
{
    private SessionValidationService&MockObject $sessionValidationService;
    private RememberMeService&MockObject $rememberMeService;
    private CookieFactoryService&MockObject $cookieFactory;
    private RedirectTokenProviderInterface&MockObject $redirectTokenProvider;

    protected function setUp(): void
    {
        $this->sessionValidationService = $this->createMock(SessionValidationService::class);
        $this->rememberMeService = $this->createMock(RememberMeService::class);
        $this->cookieFactory = $this->createMock(CookieFactoryService::class);
        $this->redirectTokenProvider = $this->createMock(RedirectTokenProviderInterface::class);
    }

    public function testWebSessionFailureRedirectsToLoginWithTokenAndPreservesQuery(): void
    {
        $middleware = new SessionGuardMiddleware(
            $this->sessionValidationService,
            $this->rememberMeService,
            $this->cookieFactory,
            $this->redirectTokenProvider
        );

        $this->redirectTokenProvider->expects($this->once())
            ->method('issue')
            ->with('/dashboard?tab=security')
            ->willReturn('signed-token');

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/dashboard?tab=security')
            ->withAttribute(RequestContext::class, new RequestContext('r', '127.0.0.1', 'ua'));

        $response = $middleware->process($request, $this->createMock(RequestHandlerInterface::class));

        self::assertSame(302, $response->getStatusCode());
        self::assertSame('/login?r=signed-token', $response->getHeaderLine('Location'));
    }

    public function testApiSessionFailureReturns401WithoutRedirectToken(): void
    {
        $middleware = new SessionGuardMiddleware(
            $this->sessionValidationService,
            $this->rememberMeService,
            $this->cookieFactory,
            $this->redirectTokenProvider
        );

        $this->redirectTokenProvider->expects($this->never())->method('issue');

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/api/v1/admins')
            ->withAttribute(RequestContext::class, new RequestContext('r', '127.0.0.1', 'ua'));

        $response = $middleware->process($request, $this->createMock(RequestHandlerInterface::class));

        self::assertSame(401, $response->getStatusCode());
        self::assertSame('', $response->getHeaderLine('Location'));
        self::assertSame('application/json', $response->getHeaderLine('Content-Type'));
    }

    public function testRememberFallbackSuccessDoesNotRedirect(): void
    {
        $middleware = new SessionGuardMiddleware(
            $this->sessionValidationService,
            $this->rememberMeService,
            $this->cookieFactory,
            $this->redirectTokenProvider
        );

        $this->rememberMeService->expects($this->once())
            ->method('processAutoLogin')
            ->willReturn(['session_token' => 'new-auth', 'remember_me_token' => 'new-rm']);

        $this->sessionValidationService->expects($this->once())
            ->method('validate')
            ->with('new-auth', $this->isInstanceOf(RequestContext::class))
            ->willReturn(10);

        $this->cookieFactory->method('createSessionCookie')->willReturn('auth_token=new-auth; Path=/');
        $this->cookieFactory->method('createRememberMeCookie')->willReturn('remember_me=new-rm; Path=/');

        $this->redirectTokenProvider->expects($this->never())->method('issue');

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/dashboard')
            ->withAttribute(RequestContext::class, new RequestContext('r', '127.0.0.1', 'ua'))
            ->withCookieParams(['remember_me' => 'old-rm']);

        $handler = new class() implements RequestHandlerInterface {
            public function handle(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
            {
                return (new Response())->withStatus(200);
            }
        };

        $response = $middleware->process($request, $handler);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('', $response->getHeaderLine('Location'));
    }
}
