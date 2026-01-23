<?php

declare(strict_types=1);

namespace Maatify\SecuritySignals\Contract;

use Maatify\SecuritySignals\Enum\SecuritySignalActorTypeEnum;
use Maatify\SecuritySignals\Enum\SecuritySignalSeverityEnum;

interface SecuritySignalsPolicyInterface
{
    /**
     * Normalize the actor type to a valid string value.
     * Defaults to ANONYMOUS if invalid.
     */
    public function normalizeActorType(string|SecuritySignalActorTypeEnum $actorType): string;

    /**
     * Normalize the severity to a valid string value.
     * Defaults to INFO if invalid.
     */
    public function normalizeSeverity(string|SecuritySignalSeverityEnum $severity): string;

    /**
     * Check if metadata JSON size is within limits (e.g. 64KB).
     */
    public function validateMetadataSize(string $json): bool;
}
