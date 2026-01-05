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
    public function __construct(private SessionValidationService $sessionValidationService)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $token = $this->extractToken($request);

        if ($token === null) {
            return $handler->handle($request);
        }

        try {
            $this->sessionValidationService->validate($token);

            // If we are here, the user is authenticated.
            return $this->handleAuthenticated($request);

        } catch (InvalidSessionException | ExpiredSessionException | RevokedSessionException $e) {
            // Token is invalid, treat as guest.
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
            // Strict cast to string to satisfy PHPStan if cookie params are mixed
            return (string)$cookies['auth_token'];
        }

        return null;
    }

    private function handleAuthenticated(ServerRequestInterface $request): ResponseInterface
    {
        // Detect if Web request
        $acceptHeader = $request->getHeaderLine('Accept');
        $isWeb = str_contains($acceptHeader, 'text/html');

        if ($isWeb) {
            $response = new Response();
            return $response->withHeader('Location', '/dashboard')->withStatus(302);
        }

        // For API, we strictly deny access to guest-only routes if authenticated.
        $response = new Response();
        $payload = json_encode(['error' => 'Already authenticated.'], JSON_THROW_ON_ERROR);
        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(403);
    }
}
