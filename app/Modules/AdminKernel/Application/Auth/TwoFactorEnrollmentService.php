<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-29 10:07
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Application\Auth;

use Maatify\AdminKernel\Application\Auth\DTO\TwoFactorSetupPageDTO;
use Maatify\AdminKernel\Application\Auth\DTO\TwoFactorSetupRequestDTO;
use Maatify\AdminKernel\Application\Auth\DTO\TwoFactorSetupResultDTO;
use Maatify\AdminKernel\Application\Services\DiagnosticsTelemetryService;
use Maatify\AdminKernel\Domain\Contracts\TotpServiceInterface;
use Maatify\AdminKernel\Domain\Service\StepUpService;
use Throwable;

final readonly class TwoFactorEnrollmentService
{
    public function __construct(
        private StepUpService $stepUpService,
        private TotpServiceInterface $totpService,
        private DiagnosticsTelemetryService $telemetryService,
    )
    {
    }

    public function buildSetupPage(): TwoFactorSetupPageDTO
    {
        $secret = $this->totpService->generateSecret();

        return new TwoFactorSetupPageDTO($secret);
    }

    public function enableTotp(TwoFactorSetupRequestDTO $request): TwoFactorSetupResultDTO
    {
        $enabled = $this->stepUpService->enableTotp(
            $request->adminId,
            $request->sessionId,
            $request->secret,
            $request->code,
            $request->requestContext
        );

        // Telemetry (best-effort): Web UI 2FA setup mutation
        try {
            $context = $request->requestContext;

            $this->telemetryService->recordEvent(
                eventKey : 'resource_mutation',
                severity : $enabled ? 'INFO' : 'WARNING',
                actorType: 'ADMIN',
                actorId  : $request->adminId,
                metadata : [
                    'action'     => '2fa_setup',
                    'result'     => $enabled ? 'success' : 'failure',
                    'request_id' => $context->requestId,
                    'ip_address' => $context->ipAddress,
                    'user_agent' => $context->userAgent,
                    'route_name' => $context->routeName,
                ]
            );
        } catch (Throwable) {
            // swallow — telemetry must never affect request flow
        }

        return new TwoFactorSetupResultDTO(
            success: $enabled,
            secret : $request->secret,
        );
    }
}
