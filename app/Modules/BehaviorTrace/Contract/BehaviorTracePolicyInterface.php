<?php

declare(strict_types=1);

namespace Maatify\BehaviorTrace\Contract;

use Maatify\BehaviorTrace\Enum\BehaviorTraceActorTypeEnum;

interface BehaviorTracePolicyInterface
{
    public function normalizeActorType(string|BehaviorTraceActorTypeEnum $actorType): BehaviorTraceActorTypeEnum;

    public function validatePayloadSize(string $jsonPayload): bool;
}
