<?php

declare(strict_types=1);

namespace App\Application\Services;

use Maatify\AuditTrail\Enum\AuditTrailActorTypeEnum;
use Maatify\AuditTrail\Recorder\AuditTrailRecorder;
use Psr\Log\LoggerInterface;

class AuditTrailService
{
    public function __construct(
        private readonly AuditTrailRecorder $recorder,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Records an audit trail event.
     *
     * This method acts as a project-facing wrapper for the AuditTrailRecorder.
     * It enforces Fail-Open behavior (Best Effort), meaning exceptions during recording
     * are suppressed (logged to fallback) and will NOT crash the application.
     *
     * @param string $eventKey
     * @param string|AuditTrailActorTypeEnum $actorType
     * @param int|null $actorId
     * @param string $entityType
     * @param int|null $entityId
     * @param string|null $subjectType
     * @param int|null $subjectId
     * @param array<string, mixed>|null $metadata
     * @param string|null $referrerRouteName
     * @param string|null $referrerPath
     * @param string|null $referrerHost
     * @param string|null $correlationId
     * @param string|null $requestId
     * @param string|null $routeName
     * @param string|null $ipAddress
     * @param string|null $userAgent
     */
    public function record(
        string $eventKey,
        string|AuditTrailActorTypeEnum $actorType,
        ?int $actorId,
        string $entityType,
        ?int $entityId,
        ?string $subjectType = null,
        ?int $subjectId = null,
        ?array $metadata = null,
        ?string $referrerRouteName = null,
        ?string $referrerPath = null,
        ?string $referrerHost = null,
        ?string $correlationId = null,
        ?string $requestId = null,
        ?string $routeName = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): void {
        try {
            $this->recorder->record(
                $eventKey,
                $actorType,
                $actorId,
                $entityType,
                $entityId,
                $subjectType,
                $subjectId,
                $metadata,
                $referrerRouteName,
                $referrerPath,
                $referrerHost,
                $correlationId,
                $requestId,
                $routeName,
                $ipAddress,
                $userAgent
            );
        } catch (\Throwable $e) {
            // Fail-open: suppress all exceptions to prevent application crash
            $this->logger->error('AuditTrailService: Failed to record event', [
                'exception' => $e,
                'event_key' => $eventKey,
            ]);
        }
    }
}
