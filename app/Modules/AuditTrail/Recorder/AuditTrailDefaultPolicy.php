<?php

declare(strict_types=1);

namespace Maatify\AuditTrail\Recorder;

use Maatify\AuditTrail\Contract\AuditTrailPolicyInterface;
use Maatify\AuditTrail\Enum\AuditTrailActorTypeEnum;

class AuditTrailDefaultPolicy implements AuditTrailPolicyInterface
{
    private const MAX_METADATA_SIZE = 65535;

    public function normalizeActorType(string|AuditTrailActorTypeEnum $actorType): string
    {
        if ($actorType instanceof AuditTrailActorTypeEnum) {
            return $actorType->value;
        }

        // Try to match string to Enum case
        $upper = strtoupper($actorType);
        $case = AuditTrailActorTypeEnum::tryFrom($upper);

        return $case ? $case->value : AuditTrailActorTypeEnum::ANONYMOUS->value;
    }

    public function validateMetadataSize(string $json): bool
    {
        return strlen($json) <= self::MAX_METADATA_SIZE;
    }
}
