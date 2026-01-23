<?php

declare(strict_types=1);

namespace Maatify\BehaviorTrace\Contract;

use Maatify\BehaviorTrace\Enum\BehaviorTraceActorTypeInterface;

interface BehaviorTracePolicyInterface
{
    public function normalizeActorType(string|BehaviorTraceActorTypeInterface $actorType): BehaviorTraceActorTypeInterface;

    public function validateMetadataSize(string $json): bool;
}
