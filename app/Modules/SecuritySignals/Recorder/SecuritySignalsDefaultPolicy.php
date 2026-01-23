<?php

declare(strict_types=1);

namespace Maatify\SecuritySignals\Recorder;

use Maatify\SecuritySignals\Contract\SecuritySignalsPolicyInterface;
use Maatify\SecuritySignals\Enum\SecuritySignalActorTypeEnum;
use Maatify\SecuritySignals\Enum\SecuritySignalSeverityEnum;

class SecuritySignalsDefaultPolicy implements SecuritySignalsPolicyInterface
{
    private const MAX_METADATA_SIZE = 65535;

    public function normalizeActorType(string|SecuritySignalActorTypeEnum $actorType): string
    {
        if ($actorType instanceof SecuritySignalActorTypeEnum) {
            return $actorType->value;
        }

        // Try to match string to Enum case
        $upper = strtoupper($actorType);
        $case = SecuritySignalActorTypeEnum::tryFrom($upper);

        return $case ? $case->value : SecuritySignalActorTypeEnum::ANONYMOUS->value;
    }

    public function normalizeSeverity(string|SecuritySignalSeverityEnum $severity): string
    {
        if ($severity instanceof SecuritySignalSeverityEnum) {
            return $severity->value;
        }

        $upper = strtoupper($severity);
        $case = SecuritySignalSeverityEnum::tryFrom($upper);

        return $case ? $case->value : SecuritySignalSeverityEnum::INFO->value;
    }

    public function validateMetadataSize(string $json): bool
    {
        return strlen($json) <= self::MAX_METADATA_SIZE;
    }
}
