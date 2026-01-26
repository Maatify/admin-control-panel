<?php

declare(strict_types=1);

namespace App\Infrastructure\Logging;

use App\Application\Contracts\AuditTrailRecorderInterface;
use Maatify\AuditTrail\Recorder\AuditTrailRecorder;
use InvalidArgumentException;

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
        if ($entityType === null) {
            // Strict Fail-Open: The Service catches Throwable.
            // Throwing here is safer than guessing a default value.
            throw new InvalidArgumentException('AuditTrail: entityType is required by recorder but was null.');
        }

        $this->recorder->record(
            eventKey: $eventKey,
            actorType: $actorType,
            actorId: $actorId,
            entityType: $entityType,
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
