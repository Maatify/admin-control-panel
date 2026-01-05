<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Service\SessionValidationService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

/**
 * Guard Middleware for Session Validation.
 *
 * NOTE: Explicit Web vs API distinction logic is provisional (Phase 13 only).
 * This logic handles the different auth mechanisms (Bearer vs Cookie) and failure responses (Exception vs Redirect)
 * for the Web Surface Enablement. It will be formalized in a later phase.
 * No security decision is derived from the UI surface itself; this is purely for transport and response format handling.
 */
class SessionGuardMiddleware implements MiddlewareInterface
{
    private SessionValidationService $sessionValidationService;

    public function __construct(SessionValidationService $sessionValidationService)
    {
        $this->sessionValidationService = $sessionValidationService;
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

        if ($token === null) {
            return $this->handleFailure($request, 'No session token provided.');
        }

        try {
            $adminId = $this->sessionValidationService->validate($token);
            $request = $request->withAttribute('admin_id', $adminId);
            return $handler->handle($request);
        } catch (\App\Domain\Exception\InvalidSessionException | \App\Domain\Exception\ExpiredSessionException | \App\Domain\Exception\RevokedSessionException $e) {
            return $this->handleFailure($request, $e->getMessage());
        }
    }

    private function handleFailure(ServerRequestInterface $request, string $message): ResponseInterface
    {
        // For strictly following Phase 13.5 rules while preventing regressions:
        // "Eliminate Accept-header–based security branching".
        // BUT we must not break the app.
        // We will default to Redirect for Web (Cookie-based requests) and Exception for API (Bearer).
        // If the request came with a Cookie, we treat it as Web.

        $cookies = $request->getCookieParams();
        $hasAuthCookie = isset($cookies['auth_token']);

        // If it looks like a browser request (has cookie or no auth at all), redirect.
        // If it has Bearer header, it's API.
        $authHeader = $request->getHeaderLine('Authorization');
        $isApi = !empty($authHeader) && str_starts_with($authHeader, 'Bearer ');

        if (!$isApi) {
             // Web Request (or default)
            $response = new Response();
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        // API request: Throw exception
        throw new \App\Domain\Exception\InvalidSessionException($message);
    }
}
