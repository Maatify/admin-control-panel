<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Application\Services\DiagnosticsTelemetryService;
use App\Context\AdminContext;
use App\Context\RequestContext;
use App\Domain\Contracts\AdminSessionValidationRepositoryInterface;
use App\Domain\Contracts\SecurityEventLoggerInterface;
use App\Domain\DTO\SecurityEventDTO;
use App\Domain\Service\AdminAuthenticationService;
use App\Domain\Service\RememberMeService;
use DateTimeImmutable;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Throwable;

readonly class LogoutController
{
    public function __construct(
        private AdminSessionValidationRepositoryInterface $sessionRepository,
        private RememberMeService $rememberMeService,
        private SecurityEventLoggerInterface $securityEventLogger,
        private AdminAuthenticationService $authService,
        private DiagnosticsTelemetryService $telemetryService
    ) {
    }

    public function logout(Request $request, Response $response): Response
    {
        $adminContext = $request->getAttribute(AdminContext::class);
        if (!$adminContext instanceof AdminContext) {
            throw new \RuntimeException('AdminContext missing');
        }
        $adminId = $adminContext->adminId;

        // Check for session token in cookies
        $cookies = $request->getCookieParams();
        $token = isset($cookies['auth_token']) ? (string)$cookies['auth_token'] : null;

        $context = $request->getAttribute(RequestContext::class);
        if (!$context instanceof RequestContext) {
            throw new \RuntimeException('Request context missing');
        }

        // ─────────────────────────────
        // Security Event (Authoritative)
        // ─────────────────────────────
        $this->securityEventLogger->log(new SecurityEventDTO(
            $adminId,
            'admin_logout',
            'info',
            [],
            $context->ipAddress,
            $context->userAgent,
            new DateTimeImmutable(),
            $context->requestId
        ));

        // ─────────────────────────────
        // 🔍 Telemetry (best-effort)
        // ─────────────────────────────
        try {
            $this->telemetryService->recordEvent(
                eventKey: 'resource_mutation',
                severity: 'INFO',
                actorType: 'ADMIN',
                actorId: $adminId,
                metadata: [
                    'action'     => 'self_logout',
                    'session_id'=> $token,
                    'request_id' => $context->requestId,
                    'ip_address' => $context->ipAddress,
                    'user_agent' => $context->userAgent,
                    'route_name' => $context->routeName,
                ]
            );
        } catch (Throwable) {
            // swallow — telemetry must never affect logout flow
        }

        // Token + Identity Binding Check
        if ($token !== null) {
            $session = $this->sessionRepository->findSession($token);

            // Only invalidate if the session belongs to the current admin
            if ($session !== null && (int)$session['admin_id'] === $adminId) {
                $this->authService->logoutSession($adminId, $token, $context);
            }
        }

        // Revoke Remember-Me tokens for this admin
        if (isset($cookies['remember_me'])) {
            $parts = explode(':', (string)$cookies['remember_me']);
            if (count($parts) === 2) {
                $this->rememberMeService->revokeBySelector($parts[0], $context);
            }
        }

        // Always clear the cookie (Idempotency)
        $isSecure = $request->getUri()->getScheme() === 'https';
        $secureFlag = $isSecure ? 'Secure;' : '';

        $cookieHeader = sprintf(
            'auth_token=; Path=/; HttpOnly; SameSite=Strict; Max-Age=0; %s',
            $secureFlag
        );
        $cookieHeader = trim($cookieHeader, '; ');

        $rememberMeClear = sprintf(
            'remember_me=; Path=/; HttpOnly; SameSite=Strict; Max-Age=0; %s',
            $secureFlag
        );

        return $response
            ->withAddedHeader('Set-Cookie', $cookieHeader)
            ->withAddedHeader('Set-Cookie', $rememberMeClear)
            ->withHeader('Location', '/login')
            ->withStatus(302);
    }
}
