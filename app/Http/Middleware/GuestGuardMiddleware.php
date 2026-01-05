<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Service\SessionValidationService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use App\Domain\Exception\InvalidSessionException;
use App\Domain\Exception\ExpiredSessionException;
use App\Domain\Exception\RevokedSessionException;
use Slim\Psr7\Response;

class GuestGuardMiddleware implements MiddlewareInterface
{
    public function __construct(
        private SessionValidationService $sessionValidationService,
        private bool $isApi = false
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $token = $this->extractToken($request);

        // If no token, user is definitely a guest. Proceed.
        if ($token === null) {
            return $handler->handle($request);
        }

        try {
            // Check if the request is already authenticated.
            $this->sessionValidationService->validate($token);

            // If we are here, the user is authenticated.
            return $this->handleAuthenticated();

        } catch (InvalidSessionException | ExpiredSessionException | RevokedSessionException $e) {
            // Token is invalid (expired, revoked, or malformed). Treat as guest.
            return $handler->handle($request);
        }
    }

    private function extractToken(ServerRequestInterface $request): ?string
    {
        $authHeader = $request->getHeaderLine('Authorization');
        if (!empty($authHeader) && str_starts_with($authHeader, 'Bearer ')) {
            return substr($authHeader, 7);
        }

        $cookies = $request->getCookieParams();
        if (isset($cookies['auth_token'])) {
            return (string)$cookies['auth_token'];
        }

        return null;
    }

    private function handleAuthenticated(): ResponseInterface
    {
        if ($this->isApi) {
            // API Mode: Return 403 Forbidden
            $response = new Response();
            $payload = json_encode(['error' => 'Already authenticated.'], JSON_THROW_ON_ERROR);
            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(403);
        }

        // Web Mode: Redirect to Dashboard
        $response = new Response();
        return $response->withHeader('Location', '/dashboard')->withStatus(302);
    }
}
