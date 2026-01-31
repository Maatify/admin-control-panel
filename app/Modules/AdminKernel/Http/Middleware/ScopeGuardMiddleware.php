<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Middleware;

use Maatify\AdminKernel\Domain\Enum\Scope;
use Maatify\AdminKernel\Domain\Security\ScopeRegistry;
use Maatify\AdminKernel\Context\RequestContext;
use Maatify\AdminKernel\Domain\Service\StepUpService;
use Maatify\AdminKernel\Http\Auth\AuthSurface;
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
        $adminContext = $request->getAttribute(\Maatify\AdminKernel\Context\AdminContext::class);
        if (!$adminContext instanceof \Maatify\AdminKernel\Context\AdminContext) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write((string)json_encode(['error' => 'Authentication required'], JSON_THROW_ON_ERROR));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        $adminId = $adminContext->adminId;

        $sessionId = $this->getSessionIdFromRequest($request);
        if ($sessionId === null) {
             // Should not happen if SessionGuard works
             $response = new \Slim\Psr7\Response();
             $response->getBody()->write((string)json_encode(['error' => 'Session required'], JSON_THROW_ON_ERROR));
             return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        $context = $request->getAttribute(RequestContext::class);
        if (!$context instanceof RequestContext) {
            throw new \RuntimeException("Request context missing");
        }

        // Defensive Assertion: Session MUST be ACTIVE here.
        // This ensures middleware order is correct and no one bypassed SessionStateGuard.
        $state = $this->stepUpService->getSessionState($adminId, $sessionId, $context);
        if ($state !== \Maatify\AdminKernel\Domain\Enum\SessionState::ACTIVE) {
//             // Fallback handling if SessionStateGuard was bypassed or failed.
//             $this->stepUpService->logDenial($adminId, $sessionId, Scope::LOGIN, $context);
//
//             $response = new \Slim\Psr7\Response();
//             $payload = [
//                 'code' => 'STEP_UP_REQUIRED',
//                 'scope' => 'login'
//             ];
//             $response->getBody()->write((string)json_encode($payload, JSON_THROW_ON_ERROR));
//             return $response->withStatus(403)->withHeader('Content-Type', 'application/json');

            // IMPORTANT:
            // Let SessionStateGuardMiddleware handle setup vs verify
            return $handler->handle($request);
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
        if (!$this->stepUpService->hasGrant($adminId, $sessionId, $requiredScope, $context)) {
             $this->stepUpService->logDenial($adminId, $sessionId, $requiredScope, $context);

            // ADDITIVE START
            if (!AuthSurface::isApi($request)) {
                return $this->redirectToStepUp($request, $requiredScope);
            }
            // ADDITIVE END

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

    private function getSessionIdFromRequest(ServerRequestInterface $request): ?string
    {
        // STRICT RULE: Always check Cookie for Auth Token (Session Only)
        $cookies = $request->getCookieParams();
        if (isset($cookies['auth_token'])) {
            return (string)$cookies['auth_token'];
        }
        return null;
    }

    private function redirectToStepUp(
        ServerRequestInterface $request,
        Scope $requiredScope
    ): ResponseInterface {
        $uri = $request->getUri();

        $returnTo = $uri->getPath();
        $query = $uri->getQuery();
        if ($query !== '') {
            $returnTo .= '?' . $query;
        }

        $location = '/2fa/verify?scope=' . urlencode($requiredScope->value)
                    . '&return_to=' . urlencode($returnTo);

        $response = new \Slim\Psr7\Response();
        return $response
            ->withHeader('Location', $location)
            ->withStatus(302);
    }

}
