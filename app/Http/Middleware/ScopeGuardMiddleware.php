<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Enum\Scope;
use App\Domain\Security\ScopeRegistry;
use App\Domain\Service\StepUpService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Routing\RouteContext;

class ScopeGuardMiddleware implements MiddlewareInterface
{
    public function __construct(
        private StepUpService $stepUpService
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // 1. Assumption: SessionStateGuardMiddleware has already run.
        // Therefore, the session is ACTIVE. We do not check for Scope::LOGIN.

        $adminId = $request->getAttribute('admin_id');
        if (!is_int($adminId)) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode(['error' => 'Authentication required']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        $sessionId = $this->getSessionIdFromRequest($request);
        if ($sessionId === null) {
             // Should not happen if SessionGuard works
             $response = new \Slim\Psr7\Response();
             $response->getBody()->write(json_encode(['error' => 'Session required']));
             return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
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
             $response->getBody()->write(json_encode($payload));
             return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }

        return $handler->handle($request);
    }

    private function getSessionIdFromRequest(ServerRequestInterface $request): ?string
    {
        // Extract Bearer token again? Or rely on attribute if SessionGuard sets it?
        // SessionGuard sets 'admin_id'. It does NOT set session_id in attributes based on previous code.
        // We need to extract it from header.
        $header = $request->getHeaderLine('Authorization');
        if (preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            return $matches[1];
        }
        return null;
    }
}
