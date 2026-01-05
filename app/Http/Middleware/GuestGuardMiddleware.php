<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Exception\ExpiredSessionException;
use App\Domain\Exception\InvalidSessionException;
use App\Domain\Exception\RevokedSessionException;
use App\Domain\Service\SessionValidationService;
use App\Http\Auth\AuthSurface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

// Phase 13.7 LOCK: Auth surface detection MUST use AuthSurface::isApi()
class GuestGuardMiddleware implements MiddlewareInterface
{
    private SessionValidationService $sessionValidationService;

    public function __construct(SessionValidationService $sessionValidationService, bool $isApi = false)
    {
        $this->sessionValidationService = $sessionValidationService;
        // $isApi is preserved for constructor compatibility but ignored in favor of AuthSurface
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $token = null;
        $isApi = AuthSurface::isApi($request);

        // STRICT SEPARATION: API checks Bearer, Web checks Cookie.
        if ($isApi) {
            $authHeader = $request->getHeaderLine('Authorization');
            if (!empty($authHeader) && str_starts_with($authHeader, 'Bearer ')) {
                $token = substr($authHeader, 7);
            }
        } else {
            $cookies = $request->getCookieParams();
            if (isset($cookies['auth_token'])) {
                $token = $cookies['auth_token'];
            }
        }

        // If no token found in the expected source, proceed as guest
        if ($token === null) {
            return $handler->handle($request);
        }

        try {
            // Check if session is valid
            $this->sessionValidationService->validate($token);

            // Session is valid. Block access.
            if ($isApi) {
                $response = new Response();
                $response->getBody()->write(json_encode(['error' => 'Already authenticated.'], JSON_THROW_ON_ERROR));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(403);
            } else {
                $response = new Response();
                return $response
                    ->withHeader('Location', '/dashboard')
                    ->withStatus(302);
            }
        } catch (InvalidSessionException | ExpiredSessionException | RevokedSessionException $e) {
            // Session is invalid/expired/revoked. Proceed as guest.
            return $handler->handle($request);
        }
    }
}
