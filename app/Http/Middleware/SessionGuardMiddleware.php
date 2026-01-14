<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Service\SessionValidationService;
use App\Http\Auth\AuthSurface;
use DI\Container;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

// Phase 13.7 LOCK: Auth surface detection MUST use AuthSurface::isApi()
/**
 * Guard Middleware for Session Validation.
 *
 * PHASE 13.7: Auth Boundary Lock & Regression Guard
 * - STRICT Web vs API detection (path-based).
 * - Explicit failure responses (401 JSON vs 302 Redirect).
 * - Canonical exception handling.
 */
class SessionGuardMiddleware implements MiddlewareInterface
{
    private SessionValidationService $sessionValidationService;

    public function __construct(
        SessionValidationService $sessionValidationService,
        private Container $container
    ) {
        $this->sessionValidationService = $sessionValidationService;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // 1. Detect Mode: API vs Web (Response Format Only)
        // STRICT RULE: Path implies API intent for error handling.
        $isApi = AuthSurface::isApi($request);

        $token = null;

        // STRICT RULE: Always check Cookie for Auth Token (Session Only)
        $cookies = $request->getCookieParams();
        if (isset($cookies['auth_token'])) {
            $token = $cookies['auth_token'];
        }

        if ($token === null) {
            return $this->handleFailure($isApi, 'No session token provided.');
        }

        try {
            $adminId = $this->sessionValidationService->validate($token);
            $request = $request->withAttribute('admin_id', $adminId);

            // Update container with the authenticated request
            $this->container->set(ServerRequestInterface::class, $request);

            return $handler->handle($request);
        } catch (\App\Domain\Exception\InvalidSessionException | \App\Domain\Exception\ExpiredSessionException | \App\Domain\Exception\RevokedSessionException $e) {
            return $this->handleFailure($isApi, $e->getMessage());
        }
    }

    private function handleFailure(bool $isApi, string $message): ResponseInterface
    {
        if ($isApi) {
            // API Failure: 401 Unauthorized (JSON)
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write((string)json_encode(['error' => $message], JSON_THROW_ON_ERROR));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        } else {
            // Web Failure: Redirect to Login
            $response = new \Slim\Psr7\Response();
            return $response->withHeader('Location', '/login')->withStatus(302);
        }
    }
}
