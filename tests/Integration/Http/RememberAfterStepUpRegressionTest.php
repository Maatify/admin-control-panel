<?php

declare(strict_types=1);

namespace Tests\Integration\Http;

use Maatify\AdminKernel\Context\RequestContext;
use Maatify\AdminKernel\Domain\Service\SessionValidationService;
use Maatify\AdminKernel\Domain\Service\RememberMeService;
use Maatify\AdminKernel\Http\Cookie\CookieFactoryService;
use Maatify\AdminKernel\Http\Middleware\SessionGuardMiddleware;
use Maatify\AdminKernel\Domain\Exception\ExpiredSessionException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;

final class RememberAfterStepUpRegressionTest extends TestCase
{
    public function test_remember_me_auto_login_after_session_expired(): void
    {
        // 🔹 Mock expired session
        $sessionValidation = $this->createMock(SessionValidationService::class);
        $sessionValidation->method('validate')
            ->willReturnOnConsecutiveCalls(
                $this->throwException(new ExpiredSessionException('expired')),
                123
            );

        // 🔹 Mock remember success
        $rememberService = $this->createMock(RememberMeService::class);
        $rememberService->method('processAutoLogin')
            ->willReturn([
                'session_token' => 'new_session',
                'remember_me_token' => 'new_selector:new_validator'
            ]);

        $cookieFactory = $this->createMock(CookieFactoryService::class);
        $cookieFactory->method('createSessionCookie')
            ->willReturn('auth_token=new_session; Path=/; HttpOnly;');
        $cookieFactory->method('createRememberMeCookie')
            ->willReturn('remember_me=new_selector:new_validator; Path=/; HttpOnly;');

        $middleware = new SessionGuardMiddleware(
            $sessionValidation,
            $rememberService,
            $cookieFactory
        );

        // 🔹 Build request with expired auth_token + remember_me
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/dashboard')
            ->withCookieParams([
                'auth_token' => 'expired_token',
                'remember_me' => 'old_selector:old_validator'
            ]);

        $request = $request->withAttribute(
            RequestContext::class,
            new RequestContext('req1', '127.0.0.1', 'phpunit')
        );

        $finalHandler = new class() implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response();
            }
        };

        // Act
        $response = $middleware->process($request, $finalHandler);

        // Assert: New cookies are written
        $cookies = $response->getHeader('Set-Cookie');

        self::assertNotEmpty($cookies);

        $sessionFound = false;

        foreach ($cookies as $cookie) {
            if (str_contains($cookie, 'auth_token=new_session')) {
                $sessionFound = true;
                break;
            }
        }

        self::assertTrue($sessionFound, 'Session cookie was not reissued');
    }
}
