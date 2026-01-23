<?php

declare(strict_types=1);

namespace Maatify\BehaviorTrace\Recorder;

use Maatify\BehaviorTrace\Contract\BehaviorTracePolicyInterface;
use Maatify\BehaviorTrace\Enum\BehaviorTraceActorTypeEnum;

class BehaviorTraceDefaultPolicy implements BehaviorTracePolicyInterface
{
    public function normalizeActorType(string|BehaviorTraceActorTypeEnum $actorType): BehaviorTraceActorTypeEnum
    {
        if ($actorType instanceof BehaviorTraceActorTypeEnum) {
            return $actorType;
        }

        return BehaviorTraceActorTypeEnum::tryFrom($actorType) ?? BehaviorTraceActorTypeEnum::ANONYMOUS;
    }

    public function validatePayloadSize(string $jsonPayload): bool
    {
        return strlen($jsonPayload) <= 65536;
    }
}
