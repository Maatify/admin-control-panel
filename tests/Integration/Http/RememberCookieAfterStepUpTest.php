<?php

declare(strict_types=1);

namespace Tests\Integration\Http;

use Maatify\AdminKernel\Context\RequestContext;
use Maatify\AdminKernel\Domain\Exception\ExpiredSessionException;
use Maatify\AdminKernel\Domain\Service\SessionValidationService;
use Maatify\AdminKernel\Domain\Service\RememberMeService;
use Maatify\AdminKernel\Http\Cookie\CookieFactoryService;
use Maatify\AdminKernel\Http\Middleware\SessionGuardMiddleware;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class RememberCookieAfterStepUpTest extends TestCase
{
    public function test_remember_cookie_is_reissued_after_stepup(): void
    {
        // First validate throws expired, second succeeds
        $sessionValidation = $this->createMock(SessionValidationService::class);
        $sessionValidation->method('validate')
            ->willReturnOnConsecutiveCalls(
                $this->throwException(new ExpiredSessionException('expired')),
                123
            );

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

        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/dashboard')
            ->withCookieParams([
                'auth_token' => 'expired_token',
                'remember_me' => 'old_selector:old_validator'
            ])
            ->withAttribute(
                RequestContext::class,
                new RequestContext('req1', '127.0.0.1', 'phpunit')
            );

        $finalHandler = new class() implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response();
            }
        };

        $response = $middleware->process($request, $finalHandler);

        $cookies = $response->getHeader('Set-Cookie');

        $this->assertNotEmpty($cookies, 'No cookies written after step-up');
        $rememberFound = false;

        foreach ($cookies as $cookie) {
            if (str_contains($cookie, 'remember_me=')) {
                $rememberFound = true;
                break;
            }
        }

        $this->assertTrue(
            $rememberFound,
            'remember_me cookie was not reissued after step-up'
        );
    }
}
