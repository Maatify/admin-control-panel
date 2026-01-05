<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Service\RememberMeService;
use App\Domain\Service\SessionValidationService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

/**
 * Guard Middleware for Session Validation.
 * Handles both session validation and Remember-Me auto-login.
 */
class SessionGuardMiddleware implements MiddlewareInterface
{
    private SessionValidationService $sessionValidationService;
    private RememberMeService $rememberMeService;

    public function __construct(
        SessionValidationService $sessionValidationService,
        RememberMeService $rememberMeService
    ) {
        $this->sessionValidationService = $sessionValidationService;
        $this->rememberMeService = $rememberMeService;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Check for Bearer Token
        $authHeader = $request->getHeaderLine('Authorization');
        $token = null;

        if (!empty($authHeader) && str_starts_with($authHeader, 'Bearer ')) {
            $token = substr($authHeader, 7);
        }

        // Check for Cookie if no Bearer Token
        if ($token === null) {
            $cookies = $request->getCookieParams();
            if (isset($cookies['auth_token'])) {
                $token = $cookies['auth_token'];
            }
        }

        if ($token !== null) {
            try {
                $adminId = $this->sessionValidationService->validate($token);
                $request = $request->withAttribute('admin_id', $adminId);
                return $handler->handle($request);
            } catch (\App\Domain\Exception\InvalidSessionException | \App\Domain\Exception\ExpiredSessionException | \App\Domain\Exception\RevokedSessionException $e) {
                // Session invalid. Fall through to auto-login if Web request.
            }
        }

        // Auto-Login Logic (Web Only)
        // Detect if Web request based on Accept header
        $acceptHeader = $request->getHeaderLine('Accept');
        $isWeb = str_contains($acceptHeader, 'text/html');

        if ($isWeb) {
            $cookies = $request->getCookieParams();
            if (isset($cookies['remember_me'])) {
                $result = $this->rememberMeService->processAutoLogin($cookies['remember_me']);

                if ($result !== null) {
                    // Success!
                    // 1. Set attributes
                    $request = $request->withAttribute('admin_id', $result['admin_id']);

                    // 2. Process request
                    $response = $handler->handle($request);

                    // 3. Inject new cookies into response
                    $isSecure = $request->getUri()->getScheme() === 'https';
                    $secureFlag = $isSecure ? 'Secure;' : '';

                    // Auth Cookie (Session Duration: 1 Hour)
                    $authCookie = sprintf(
                        "auth_token=%s; Path=/; HttpOnly; SameSite=Strict; Max-Age=%d; %s",
                        $result['session_token'],
                        3600,
                        $secureFlag
                    );

                    // Remember-Me Cookie (30 days)
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
                    // Invalid remember-me token. Clear it.
                    $response = $this->handleFailure($request, 'Invalid session and remember-me failed.');
                    return $response->withAddedHeader('Set-Cookie', 'remember_me=; Path=/; Max-Age=0; HttpOnly; SameSite=Strict');
                }
            }
        }

        return $this->handleFailure($request, 'No session token provided.');
    }

    private function handleFailure(ServerRequestInterface $request, string $message): ResponseInterface
    {
        // Detect if Web request based on Accept header
        $acceptHeader = $request->getHeaderLine('Accept');
        $isWeb = str_contains($acceptHeader, 'text/html');

        if ($isWeb) {
            $response = new Response();
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        // API request: Throw exception
        throw new \App\Domain\Exception\InvalidSessionException($message);
    }
}
