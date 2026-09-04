<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Middleware;

use Maatify\AdminKernel\Domain\Exception\ExpiredSessionException;
use Maatify\AdminKernel\Domain\Exception\InvalidSessionException;
use Maatify\AdminKernel\Domain\Exception\RevokedSessionException;
use Maatify\AdminKernel\Context\RequestContext;
use Maatify\AdminKernel\Domain\Service\SessionValidationService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

// Phase 13.7 LOCK: GuestGuardMiddleware MUST remain route-configured (Web vs API)
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
        $token = null;
        // Restore route-configured detection for response format
        $isApi = $this->isApi;

        // STRICT SEPARATION: Check Cookie for BOTH API and Web.
        $cookies = $request->getCookieParams();
        if (isset($cookies['auth_token'])) {
            $token = $cookies['auth_token'];
        }

        // If no token found in the expected source, proceed as guest
        if ($token === null) {
            return $handler->handle($request);
        }

        try {
            // Check if session is valid
            $context = $request->getAttribute(RequestContext::class);
            if (!$context instanceof RequestContext) {
                 throw new \RuntimeException("Request context missing");
            }
            $this->sessionValidationService->validate($token, $context);

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
