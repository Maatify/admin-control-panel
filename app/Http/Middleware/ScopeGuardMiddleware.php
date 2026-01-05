<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Enum\Scope;
use App\Domain\Security\ScopeRegistry;
use App\Domain\Service\StepUpService;
use App\Http\Auth\AuthSurface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Routing\RouteContext;

// Phase 13.7 LOCK: Auth surface detection MUST use AuthSurface::isApi()
class ScopeGuardMiddleware implements MiddlewareInterface
{
    public function __construct(
        private StepUpService $stepUpService
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $adminId = $request->getAttribute('admin_id');
        if (!is_int($adminId)) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write((string)json_encode(['error' => 'Authentication required'], JSON_THROW_ON_ERROR));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        // Detect Mode (Strict)
        $isApi = AuthSurface::isApi($request);

        $sessionId = $this->getSessionIdFromRequest($request, $isApi);
        if ($sessionId === null) {
             // Should not happen if SessionGuard works
             $response = new \Slim\Psr7\Response();
             $response->getBody()->write((string)json_encode(['error' => 'Session required'], JSON_THROW_ON_ERROR));
             return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        // Defensive Assertion: Session MUST be ACTIVE here.
        // This ensures middleware order is correct and no one bypassed SessionStateGuard.
        $state = $this->stepUpService->getSessionState($adminId, $sessionId);
        if ($state !== \App\Domain\Enum\SessionState::ACTIVE) {
             // Fallback handling if SessionStateGuard was bypassed or failed.
             $this->stepUpService->logDenial($adminId, $sessionId, Scope::LOGIN);

             $response = new \Slim\Psr7\Response();
             $payload = [
                 'code' => 'STEP_UP_REQUIRED',
                 'scope' => 'login'
             ];
             $response->getBody()->write((string)json_encode($payload, JSON_THROW_ON_ERROR));
             return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }

        // Determine required scope
        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();

        // If route is not found (404), we don't block here, let app handle it.
        if (!$route) {
            return $handler->handle($request);
        }

        $routeName = $route->getName();
        // Skip check for the step-up verification route itself to prevent loop
        if ($routeName === 'auth.stepup.verify') {
            return $handler->handle($request);
        }

        $requiredScope = ScopeRegistry::getScopeForRoute($routeName ?? '');

        // If no specific scope is required (or purely LOGIN which is handled by SessionStateGuard), pass through.
        if ($requiredScope === null || $requiredScope === Scope::LOGIN) {
            return $handler->handle($request);
        }

        // Check Specific Scope
        if (!$this->stepUpService->hasGrant($adminId, $sessionId, $requiredScope)) {
             $this->stepUpService->logDenial($adminId, $sessionId, $requiredScope);

             $response = new \Slim\Psr7\Response();
             $payload = [
                 'code' => 'STEP_UP_REQUIRED',
                 'scope' => $requiredScope->value
             ];
             $response->getBody()->write((string)json_encode($payload, JSON_THROW_ON_ERROR));
             return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }

        return $handler->handle($request);
    }

    private function getSessionIdFromRequest(ServerRequestInterface $request, bool $isApi): ?string
    {
        if ($isApi) {
            $header = $request->getHeaderLine('Authorization');
            if (preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
                return $matches[1];
            }
        } else {
            $cookies = $request->getCookieParams();
            if (isset($cookies['auth_token'])) {
                return (string)$cookies['auth_token'];
            }
        }
        return null;
    }
}
