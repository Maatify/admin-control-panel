<?php

declare(strict_types=1);

namespace App\Infrastructure\Logging;

use App\Application\Contracts\AuditTrailRecorderInterface;
use Maatify\AuditTrail\Recorder\AuditTrailRecorder;

class AuditTrailMaatifyAdapter implements AuditTrailRecorderInterface
{
    public function __construct(
        private AuditTrailRecorder $recorder
    ) {
    }

    /**
     * @param array<string, mixed>|null $metadata
     */
    public function record(
        string $eventKey,
        string $actorType,
        ?int $actorId,
        ?string $entityType = null,
        ?int $entityId = null,
        ?string $subjectType = null,
        ?int $subjectId = null,
        ?array $metadata = null
    ): void {
        $this->recorder->record(
            eventKey: $eventKey,
            actorType: $actorType,
            actorId: $actorId,
            entityType: $entityType ?? 'unknown',
            entityId: $entityId,
            subjectType: $subjectType,
            subjectId: $subjectId,
            metadata: array_merge($metadata ?? [], array_filter([
                'subject_type' => $subjectType,
                'subject_id' => $subjectId
            ], fn($v) => $v !== null)),
            referrerRouteName: null,
            referrerPath: null,
            referrerHost: null,
            correlationId: null,
            requestId: null,
            routeName: null,
            ipAddress: null,
            userAgent: null
        );
    }
}
