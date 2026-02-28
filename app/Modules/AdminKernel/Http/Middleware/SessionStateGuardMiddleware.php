<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Middleware;

use Maatify\AdminKernel\Context\RequestContext;
use Maatify\AdminKernel\Domain\Contracts\Admin\AdminTotpSecretStoreInterface;
use Maatify\AdminKernel\Domain\Enum\Scope;
use Maatify\AdminKernel\Domain\Enum\SessionState;
use Maatify\AdminKernel\Domain\Service\StepUpService;
use Maatify\AdminKernel\Http\Auth\AuthSurface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

// Phase 13.7 LOCK: Auth surface detection MUST use AuthSurface::isApi()
readonly class SessionStateGuardMiddleware implements MiddlewareInterface
{
    public function __construct(
        private StepUpService $stepUpService,
        private AdminTotpSecretStoreInterface $totpSecretStore
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $adminContext = $request->getAttribute(\Maatify\AdminKernel\Context\AdminContext::class);
        if (!$adminContext instanceof \Maatify\AdminKernel\Context\AdminContext) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(
                (string) json_encode(['error' => 'Authentication required'], JSON_THROW_ON_ERROR)
            );
            return $response
                ->withStatus(401)
                ->withHeader('Content-Type', 'application/json');
        }

        $adminId = $adminContext->adminId;

        // STRICT Detection: Same as SessionGuardMiddleware
        $isApi = AuthSurface::isApi($request);

        $sessionId = $this->getSessionIdFromRequest($request);
        if ($sessionId === null) {
            // Inconsistent state: admin_id present but no token found by this guard?
            // Should only happen if SessionGuard extraction differs or context lost.
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(
                (string) json_encode(['error' => 'Session required'], JSON_THROW_ON_ERROR)
            );
            return $response
                ->withStatus(401)
                ->withHeader('Content-Type', 'application/json');
        }

        // Skip check for Step-Up Verification route to allow promotion
        $routeContext = \Slim\Routing\RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $routeName = $route ? $route->getName() : null;

        if (
            $routeName === 'auth.stepup.verify'
            || $routeName === '2fa.setup'
            || $routeName === '2fa.enable'
            || $routeName === '2fa.verify'
        ) {
            return $handler->handle($request);
        }

        $context = $request->getAttribute(RequestContext::class);
        if (!$context instanceof RequestContext) {
            throw new \RuntimeException('Request context missing');
        }

        $state = $this->stepUpService->getSessionState($adminId, $sessionId, $context);

        if ($state !== SessionState::ACTIVE) {
            if ($isApi) {
                // API: Deny - Step Up Required (Primary/Login)
                $this->stepUpService->logDenial(
                    $adminId,
                    $sessionId,
                    Scope::LOGIN,
                    $context
                );

                throw new \Maatify\AdminKernel\Domain\Exception\StepUpRequiredException(
                    message: 'Authentication required',
                    meta: ['scope' => 'login']
                );
            }

            // Web: Redirect to 2FA Setup or Verify
            $response = new \Slim\Psr7\Response();

            if (!$this->totpSecretStore->exists($adminId)) {
                return $response
                    ->withHeader('Location', '/2fa/setup')
                    ->withStatus(302);
            }

            return $response
                ->withHeader('Location', '/2fa/verify')
                ->withStatus(302);
        }

        return $handler->handle($request);
    }

    private function getSessionIdFromRequest(ServerRequestInterface $request): ?string
    {
        $cookies = $request->getCookieParams();
        if (isset($cookies['auth_token'])) {
            return (string) $cookies['auth_token'];
        }

        return null;
    }
}
