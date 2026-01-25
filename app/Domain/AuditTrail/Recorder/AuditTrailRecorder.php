<?php

declare(strict_types=1);

namespace App\Domain\AuditTrail\Recorder;

use App\Domain\AuditTrail\DTO\AuditTrailRecordDTO;
use Maatify\AuditTrail\Recorder\AuditTrailRecorder as ModuleRecorder;

final readonly class AuditTrailRecorder implements AuditTrailRecorderInterface
{
    public function __construct(
        private ModuleRecorder $recorder
    ) {
    }

    public function record(AuditTrailRecordDTO $dto): void
    {
        $this->recorder->record(
            eventKey: $dto->eventKey,
            actorType: $dto->actorType->value,
            actorId: $dto->actorId,
            entityType: $dto->entityType,
            entityId: $dto->entityId,
            subjectType: $dto->subjectType,
            subjectId: $dto->subjectId,
            metadata: $dto->metadata,
            referrerRouteName: $dto->referrerRouteName,
            referrerPath: $dto->referrerPath,
            referrerHost: $dto->referrerHost,
            correlationId: $dto->correlationId,
            requestId: $dto->requestId,
            routeName: $dto->routeName,
            ipAddress: $dto->ipAddress,
            userAgent: $dto->userAgent
        );
    }
}
