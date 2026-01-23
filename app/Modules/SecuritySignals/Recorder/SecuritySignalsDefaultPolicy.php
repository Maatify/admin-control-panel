<?php

declare(strict_types=1);

namespace Maatify\SecuritySignals\Recorder;

use Maatify\SecuritySignals\Contract\SecuritySignalsPolicyInterface;
use Maatify\SecuritySignals\Enum\SecuritySignalActorTypeEnum;

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

    public function validateMetadataSize(string $json): bool
    {
        return strlen($json) <= self::MAX_METADATA_SIZE;
    }
}
