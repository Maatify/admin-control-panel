<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Contracts\TotpSecretRepositoryInterface;
use App\Domain\Enum\Scope;
use App\Domain\Enum\SessionState;
use App\Context\RequestContext;
use App\Domain\Service\StepUpService;
use App\Http\Auth\AuthSurface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

// Phase 13.7 LOCK: Auth surface detection MUST use AuthSurface::isApi()
class SessionStateGuardMiddleware implements MiddlewareInterface
{
    public function __construct(
        private StepUpService $stepUpService,
        private TotpSecretRepositoryInterface $totpSecretRepository
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $adminId = $request->getAttribute('admin_id');

        // Defensive check: If SessionGuard failed or wasn't run, admin_id might be missing.
        // SessionGuard should block this, but we maintain the defense.
        if (!is_int($adminId)) {
             $response = new \Slim\Psr7\Response();
             $response->getBody()->write((string)json_encode(['error' => 'Authentication required'], JSON_THROW_ON_ERROR));
             return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        // STRICT Detection: Same as SessionGuardMiddleware
        $isApi = AuthSurface::isApi($request);

        $sessionId = $this->getSessionIdFromRequest($request);
        if ($sessionId === null) {
             // Inconsistent state: admin_id present but no token found by this guard?
             // Should only happen if SessionGuard extraction differs or context lost.
             $response = new \Slim\Psr7\Response();
             $response->getBody()->write((string)json_encode(['error' => 'Session required'], JSON_THROW_ON_ERROR));
             return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        // Skip check for Step-Up Verification route to allow promotion
        $routeContext = \Slim\Routing\RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $routeName = $route ? $route->getName() : null;

        if ($routeName === 'auth.stepup.verify' || $routeName === '2fa.setup' || $routeName === '2fa.verify') {
            return $handler->handle($request);
        }

        $context = $request->getAttribute(RequestContext::class);
        if (!$context instanceof RequestContext) {
            throw new \RuntimeException("Request context missing");
        }

        $state = $this->stepUpService->getSessionState($adminId, $sessionId, $context);

        if ($state !== SessionState::ACTIVE) {
            if ($isApi) {
                 // API: Deny - Step Up Required (Primary/Login)
                 $this->stepUpService->logDenial($adminId, $sessionId, Scope::LOGIN, $context);

                 $response = new \Slim\Psr7\Response();
                 $payload = [
                     'code' => 'STEP_UP_REQUIRED',
                     'scope' => 'login'
                 ];
                 $response->getBody()->write((string)json_encode($payload, JSON_THROW_ON_ERROR));
                 return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
            } else {
                // Web: Redirect to 2FA Setup or Verify
                $response = new \Slim\Psr7\Response();
                if ($this->totpSecretRepository->get($adminId) === null) {
                    return $response->withHeader('Location', '/2fa/setup')->withStatus(302);
                } else {
                    return $response->withHeader('Location', '/2fa/verify')->withStatus(302);
                }
            }
        }

        return $handler->handle($request);
    }

    private function getSessionIdFromRequest(ServerRequestInterface $request): ?string
    {
        $cookies = $request->getCookieParams();
        if (isset($cookies['auth_token'])) {
            return (string)$cookies['auth_token'];
        }

        return null;
    }
}
