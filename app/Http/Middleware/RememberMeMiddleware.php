<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Service\RememberMeService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;
use DateTimeImmutable;

/**
 * Middleware to handle "Remember Me" persistent login.
 *
 * It runs BEFORE SessionGuardMiddleware.
 * If no session token is present but a valid remember-me cookie is found,
 * it performs an auto-login, injects the new session token into the request,
 * and queues the new cookies for the response.
 */
class RememberMeMiddleware implements MiddlewareInterface
{
    private RememberMeService $rememberMeService;

    public function __construct(RememberMeService $rememberMeService)
    {
        $this->rememberMeService = $rememberMeService;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // 1. Check if we already have an auth token (Bearer or Cookie)
        $hasAuthToken = false;
        $authHeader = $request->getHeaderLine('Authorization');
        if (!empty($authHeader) && str_starts_with($authHeader, 'Bearer ')) {
            $hasAuthToken = true;
        } else {
            $cookies = $request->getCookieParams();
            if (isset($cookies['auth_token'])) {
                $hasAuthToken = true;
            }
        }

        // 2. If no auth token, try auto-login
        if (!$hasAuthToken) {
            $cookies = $request->getCookieParams();
            if (isset($cookies['remember_me'])) {
                $result = $this->rememberMeService->processAutoLogin($cookies['remember_me']);

                if ($result !== null) {
                    // Auto-login successful!

                    // A. Mutate Request: Inject auth_token into cookies so SessionGuard sees it
                    $newCookies = $cookies;
                    $newCookies['auth_token'] = $result['session_token'];
                    $request = $request->withCookieParams($newCookies);

                    // B. Process Request
                    $response = $handler->handle($request);

                    // C. Mutate Response: Set new cookies
                    $isSecure = $request->getUri()->getScheme() === 'https';
                    $secureFlag = $isSecure ? 'Secure;' : '';

                    // Calculate Max-Age for session cookie
                    $now = new DateTimeImmutable();
                    $maxAge = $result['session_expires_at']->getTimestamp() - $now->getTimestamp();
                    if ($maxAge < 0) $maxAge = 0;

                    $authCookie = sprintf(
                        "auth_token=%s; Path=/; HttpOnly; SameSite=Strict; Max-Age=%d; %s",
                        $result['session_token'],
                        $maxAge,
                        $secureFlag
                    );

                    $rememberMeCookie = sprintf(
                        "remember_me=%s; Path=/; HttpOnly; SameSite=Strict; Max-Age=%d; %s",
                        $result['cookie_value'],
                        2592000, // 30 days
                        $secureFlag
                    );

                    return $response
                        ->withAddedHeader('Set-Cookie', trim($authCookie, '; '))
                        ->withAddedHeader('Set-Cookie', trim($rememberMeCookie, '; '));
                } else {
                    // Auto-login failed (invalid/expired/theft).
                    // We must return a Redirect to login to force the user to re-authenticate,
                    // clearing the bad cookie in the process. We do NOT pass to handler.
                    $response = new Response();
                    return $response
                        ->withHeader('Location', '/login')
                        ->withStatus(302)
                        ->withAddedHeader('Set-Cookie', 'remember_me=; Path=/; Max-Age=0; HttpOnly; SameSite=Strict');
                }
            }
        }

        // 3. Fallthrough
        return $handler->handle($request);
    }
}
