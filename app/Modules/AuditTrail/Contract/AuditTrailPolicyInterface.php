<?php

declare(strict_types=1);

namespace Maatify\AuditTrail\Contract;

use Maatify\AuditTrail\Enum\AuditTrailActorTypeEnum;

interface AuditTrailPolicyInterface
{
    /**
     * Normalize the actor type to a valid string value.
     * Defaults to ANONYMOUS if invalid.
     */
    public function normalizeActorType(string|AuditTrailActorTypeEnum $actorType): string;

    /**
     * Check if metadata JSON size is within limits (e.g. 64KB).
     */
    public function validateMetadataSize(string $json): bool;
}
