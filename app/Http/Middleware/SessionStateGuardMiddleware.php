<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Enum\Scope;
use App\Domain\Enum\SessionState;
use App\Domain\Service\StepUpService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SessionStateGuardMiddleware implements MiddlewareInterface
{
    public function __construct(
        private StepUpService $stepUpService
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $adminId = $request->getAttribute('admin_id');

        // Defensive check: If SessionGuard failed or wasn't run, admin_id might be missing.
        if (!is_int($adminId)) {
             $response = new \Slim\Psr7\Response();
             $response->getBody()->write((string)json_encode(['error' => 'Authentication required'], JSON_THROW_ON_ERROR));
             return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        $sessionId = $this->getSessionIdFromRequest($request);
        if ($sessionId === null) {
             $response = new \Slim\Psr7\Response();
             $response->getBody()->write((string)json_encode(['error' => 'Session required'], JSON_THROW_ON_ERROR));
             return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        // Skip check for Step-Up Verification route to allow promotion
        $routeContext = \Slim\Routing\RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        if ($route && $route->getName() === 'auth.stepup.verify') {
            return $handler->handle($request);
        }

        $state = $this->stepUpService->getSessionState($adminId, $sessionId);

        if ($state !== SessionState::ACTIVE) {
             // Deny - Step Up Required (Primary/Login)
             $this->stepUpService->logDenial($adminId, $sessionId, Scope::LOGIN);

             $response = new \Slim\Psr7\Response();
             $payload = [
                 'code' => 'STEP_UP_REQUIRED',
                 'scope' => 'login'
             ];
             $response->getBody()->write((string)json_encode($payload, JSON_THROW_ON_ERROR));
             return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }

        return $handler->handle($request);
    }

    private function getSessionIdFromRequest(ServerRequestInterface $request): ?string
    {
        $header = $request->getHeaderLine('Authorization');
        if (preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            return $matches[1];
        }
        return null;
    }
}
