<?php

declare(strict_types=1);

namespace App\Application\Services;

use Maatify\SecuritySignals\Enum\SecuritySignalActorTypeEnum;
use Maatify\SecuritySignals\Enum\SecuritySignalSeverityEnum;
use Maatify\SecuritySignals\Recorder\SecuritySignalsRecorder;

class SecuritySignalsService
{
    public function __construct(
        private readonly SecuritySignalsRecorder $recorder
    ) {
    }

    /**
     * Records a security signal event.
     *
     * This method acts as a project-facing wrapper for the SecuritySignalsRecorder.
     * It enforces Fail-Open behavior (Best Effort), meaning exceptions during recording
     * are suppressed (logged to fallback) and will NOT crash the application.
     *
     * @param string $signalType
     * @param string|SecuritySignalSeverityEnum $severity
     * @param string|SecuritySignalActorTypeEnum $actorType
     * @param int|null $actorId
     * @param array<string, mixed>|null $metadata
     * @param string|null $correlationId
     * @param string|null $requestId
     * @param string|null $routeName
     * @param string|null $ipAddress
     * @param string|null $userAgent
     */
    public function record(
        string $signalType,
        string|SecuritySignalSeverityEnum $severity,
        string|SecuritySignalActorTypeEnum $actorType,
        ?int $actorId,
        ?array $metadata = null,
        ?string $correlationId = null,
        ?string $requestId = null,
        ?string $routeName = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): void {
        try {
            $this->recorder->record(
                $signalType,
                $severity,
                $actorType,
                $actorId,
                $metadata,
                $correlationId,
                $requestId,
                $routeName,
                $ipAddress,
                $userAgent
            );
        } catch (\Throwable $e) {
            // Fail-open: suppress all exceptions to prevent application crash
        }
    }
}
