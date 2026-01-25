<?php

declare(strict_types=1);

namespace App\Application\Services;

use Maatify\BehaviorTrace\Enum\BehaviorTraceActorTypeInterface;
use Maatify\BehaviorTrace\Recorder\BehaviorTraceRecorder;

class BehaviorTraceService
{
    public function __construct(
        private readonly BehaviorTraceRecorder $recorder
    ) {
    }

    /**
     * Records a behavior trace event.
     *
     * This method acts as a project-facing wrapper for the BehaviorTraceRecorder.
     * It enforces Fail-Open behavior (Best Effort), meaning exceptions during recording
     * are suppressed (logged to fallback) and will NOT crash the application.
     *
     * @param string $action
     * @param BehaviorTraceActorTypeInterface|string $actorType
     * @param int|null $actorId
     * @param string|null $entityType
     * @param int|null $entityId
     * @param string|null $correlationId
     * @param string|null $requestId
     * @param string|null $routeName
     * @param string|null $ipAddress
     * @param string|null $userAgent
     * @param array<mixed>|null $metadata
     */
    public function record(
        string $action,
        BehaviorTraceActorTypeInterface|string $actorType,
        ?int $actorId = null,
        ?string $entityType = null,
        ?int $entityId = null,
        ?string $correlationId = null,
        ?string $requestId = null,
        ?string $routeName = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?array $metadata = null
    ): void {
        try {
            $this->recorder->record(
                $action,
                $actorType,
                $actorId,
                $entityType,
                $entityId,
                $correlationId,
                $requestId,
                $routeName,
                $ipAddress,
                $userAgent,
                $metadata
            );
        } catch (\Throwable $e) {
            // Fail-open: suppress all exceptions to prevent application crash
        }
    }
}
