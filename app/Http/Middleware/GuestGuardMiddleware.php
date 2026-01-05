<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Service\SessionValidationService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

class GuestGuardMiddleware implements MiddlewareInterface
{
    private SessionValidationService $sessionValidationService;
    private bool $isApi;

    public function __construct(SessionValidationService $sessionValidationService, bool $isApi = false)
    {
        $this->sessionValidationService = $sessionValidationService;
        $this->isApi = $isApi;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Extract token (Logic aligned with SessionGuardMiddleware)
        $authHeader = $request->getHeaderLine('Authorization');
        $token = null;

        if (!empty($authHeader) && str_starts_with($authHeader, 'Bearer ')) {
            $token = substr($authHeader, 7);
        }

        if ($token === null) {
            $cookies = $request->getCookieParams();
            if (isset($cookies['auth_token'])) {
                $token = $cookies['auth_token'];
            }
        }

        // If no token, proceed as guest
        if ($token === null) {
            return $handler->handle($request);
        }

        try {
            // Check if session is valid
            $this->sessionValidationService->validate($token);

            // If we are here, session is valid. Block access.
            if ($this->isApi) {
                $response = new Response();
                $response->getBody()->write(json_encode(['error' => 'Already authenticated.']) ?: '');
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(403);
            } else {
                $response = new Response();
                return $response
                    ->withHeader('Location', '/dashboard')
                    ->withStatus(302);
            }
        } catch (\Exception $e) {
            // Session is invalid/expired/revoked. Proceed as guest.
            return $handler->handle($request);
        }
    }
}
