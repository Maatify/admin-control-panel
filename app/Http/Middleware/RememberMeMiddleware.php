<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Context\RequestContext;
use App\Domain\Service\RememberMeService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use App\Domain\Exception\InvalidCredentialsException;

class RememberMeMiddleware implements MiddlewareInterface
{
    public function __construct(
        private RememberMeService $rememberMeService
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // If auth_token exists, do nothing
        $cookies = $request->getCookieParams();
        if (isset($cookies['auth_token'])) {
            return $handler->handle($request);
        }

        // If auth_token missing AND remember_me cookie exists
        if (isset($cookies['remember_me'])) {
            try {
                $context = $request->getAttribute(RequestContext::class);
                if (!$context instanceof RequestContext) {
                    throw new \RuntimeException("Request context missing");
                }
                $result = $this->rememberMeService->processAutoLogin($cookies['remember_me'], $context);
                $sessionToken = $result['session_token'];
                $newRememberMeToken = $result['remember_me_token'];

                // Inject new auth_token into request cookies so subsequent middleware sees it
                $cookies['auth_token'] = $sessionToken;
                $request = $request->withCookieParams($cookies);

                $response = $handler->handle($request);

                // Append new cookies to response
                // Session Cookie
                // We need to know max age for session cookie. Usually aligned with session expiry.
                // Since we don't have direct access to session expiry here without querying repository again,
                // and `LoginController` does it by fetching session...
                // `RememberMeService` calls `createSession`. `AdminSessionRepository` sets default expiry (e.g. 1 hour).
                // Let's assume a standard short session for auto-login (e.g. 1 hour or same as default).
                // To be precise, we might want `RememberMeService` to return expiry too, or just set session cookie duration.
                // Assuming session is temporary/short-lived compared to RememberMe.

                $isSecure = $request->getUri()->getScheme() === 'https';
                $secureFlag = $isSecure ? 'Secure;' : '';

                // Session Cookie
                $sessionCookie = sprintf(
                    "auth_token=%s; Path=/; HttpOnly; SameSite=Strict; %s",
                    $sessionToken,
                    $secureFlag
                );
                // Note: Not setting Max-Age means it's a session cookie (gone on browser close),
                // which is fine because RememberMe will restore it on restart.

                // Remember Me Cookie (30 days)
                $rememberMeCookie = sprintf(
                    "remember_me=%s; Path=/; HttpOnly; SameSite=Strict; Max-Age=%d; %s",
                    $newRememberMeToken,
                    60 * 60 * 24 * 30, // 30 days
                    $secureFlag
                );

                return $response
                    ->withAddedHeader('Set-Cookie', $sessionCookie)
                    ->withAddedHeader('Set-Cookie', $rememberMeCookie);

            } catch (InvalidCredentialsException $e) {
                // On failure: Revoke (already done in service if theft/expired), Clear cookie, Redirect to login

                // Need to clear remember_me cookie
                $isSecure = $request->getUri()->getScheme() === 'https';
                $secureFlag = $isSecure ? 'Secure;' : '';

                $clearCookie = sprintf(
                    "remember_me=; Path=/; HttpOnly; SameSite=Strict; Max-Age=0; %s",
                    $secureFlag
                );

                $response = new \Slim\Psr7\Response();
                return $response
                    ->withHeader('Set-Cookie', $clearCookie)
                    ->withHeader('Location', '/login')
                    ->withStatus(302);
            }
        }

        return $handler->handle($request);
    }
}
