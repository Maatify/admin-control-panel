<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-29 09:35
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Application\Auth;

use App\Application\Auth\DTO\AdminLogoutRequestDTO;
use App\Application\Auth\DTO\AdminLogoutResultDTO;
use App\Application\Services\DiagnosticsTelemetryService;
use App\Domain\Contracts\AdminSessionValidationRepositoryInterface;
use App\Domain\Service\AdminAuthenticationService;
use App\Domain\Service\RememberMeService;
use Throwable;

final readonly class AdminLogoutService
{
    public function __construct(
        private AdminSessionValidationRepositoryInterface $sessionRepository,
        private RememberMeService $rememberMeService,
        private AdminAuthenticationService $authService,
        private DiagnosticsTelemetryService $telemetryService,
    )
    {
    }

    public function logout(AdminLogoutRequestDTO $request): AdminLogoutResultDTO
    {
        $adminId = $request->adminId;
        $token = $request->authToken;
        $context = $request->requestContext;

        // ─────────────────────────────
        // 🔍 Telemetry (best-effort)
        // ─────────────────────────────
        try {
            $this->telemetryService->recordEvent(
                eventKey : 'resource_mutation',
                severity : 'INFO',
                actorType: 'ADMIN',
                actorId  : $adminId,
                metadata : [
                    'action'     => 'self_logout',
                    'session_id' => $token,
                    'request_id' => $context->requestId,
                    'ip_address' => $context->ipAddress,
                    'user_agent' => $context->userAgent,
                    'route_name' => $context->routeName,
                ]
            );
        } catch (Throwable) {
            // swallow — telemetry must never affect logout flow
        }

        // ─────────────────────────────
        // 🔐 Session revocation (safe)
        // ─────────────────────────────
        if ($token !== null) {
            $session = $this->sessionRepository->findSession($token);

            if ($session !== null && (int)$session['admin_id'] === $adminId) {
                $this->authService->logoutSession($adminId, $token, $context);
            }
        }

        // ─────────────────────────────
        // 🔁 Remember-me revoke
        // ─────────────────────────────
        if ($request->rememberMeCookie !== null) {
            $parts = explode(':', $request->rememberMeCookie);
            if (count($parts) === 2) {
                $this->rememberMeService->revokeBySelector($parts[0], $context);
            }
        }

        return new AdminLogoutResultDTO(
            clearAuthCookie      : true,
            clearRememberMeCookie: true,
        );
    }
}
