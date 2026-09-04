<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-29 10:10
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Application\Auth;

use Maatify\AdminKernel\Application\Auth\DTO\TwoFactorVerifyRequestDTO;
use Maatify\AdminKernel\Application\Auth\DTO\TwoFactorVerifyResultDTO;
use Maatify\AdminKernel\Application\Services\DiagnosticsTelemetryService;
use Maatify\AdminKernel\Domain\Service\StepUpService;
use Throwable;

final readonly class TwoFactorVerificationService
{
    public function __construct(
        private StepUpService $stepUpService,
        private DiagnosticsTelemetryService $telemetryService,
    )
    {
    }

    public function verifyTotp(TwoFactorVerifyRequestDTO $request): TwoFactorVerifyResultDTO
    {
        $context = $request->requestContext;

        $result = $this->stepUpService->verifyTotp(
            $request->adminId,
            $request->sessionId,
            $request->code,
            $context,
            $request->requestedScope
        );

        // Telemetry (best-effort): Web UI step-up verification
        try {
            $this->telemetryService->recordEvent(
                eventKey : $result->success ? 'auth_stepup_success' : 'auth_stepup_failure',
                severity : $result->success ? 'INFO' : 'WARNING',
                actorType: 'ADMIN',
                actorId  : $request->adminId,
                metadata : [
                    'scope'        => $request->requestedScope->value,
                    'method'       => 'totp',
                    'result'       => $result->success ? 'success' : 'failure',
                    'error_reason' => $result->success ? null : ($result->errorReason ?? 'unknown'),
                    'request_id'   => $context->requestId,
                    'ip_address'   => $context->ipAddress,
                    'user_agent'   => $context->userAgent,
                    'route_name'   => $context->routeName,
                ]
            );
        } catch (Throwable) {
            // swallow — telemetry must never affect request flow
        }

        return new TwoFactorVerifyResultDTO(
            success    : $result->success,
            errorReason: $result->success ? null : ($result->errorReason ?? 'Invalid code'),
        );
    }
}
