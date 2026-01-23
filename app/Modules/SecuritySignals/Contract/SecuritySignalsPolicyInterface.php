<?php

declare(strict_types=1);

namespace Maatify\SecuritySignals\Contract;

use Maatify\SecuritySignals\Enum\SecuritySignalActorTypeEnum;

interface SecuritySignalsPolicyInterface
{
    /**
     * Normalize the actor type to a valid string value.
     * Defaults to ANONYMOUS if invalid.
     */
    public function normalizeActorType(string|SecuritySignalActorTypeEnum $actorType): string;

    /**
     * Check if metadata JSON size is within limits (e.g. 64KB).
     */
    public function validateMetadataSize(string $json): bool;
}
