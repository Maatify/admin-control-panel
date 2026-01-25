<?php

declare(strict_types=1);

namespace App\Domain\AuditTrail\DTO;

use App\Domain\AuditTrail\Enum\AuditTrailActorTypeEnum;

readonly class AuditTrailRecordDTO
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $eventKey,
        public AuditTrailActorTypeEnum $actorType,
        public ?int $actorId,
        public string $entityType,
        public ?int $entityId,
        public ?string $subjectType = null,
        public ?int $subjectId = null,
        public array $metadata = [],
        public ?string $referrerRouteName = null,
        public ?string $referrerPath = null,
        public ?string $referrerHost = null,
        public ?string $correlationId = null,
        public ?string $requestId = null,
        public ?string $routeName = null,
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
    ) {
    }
}
