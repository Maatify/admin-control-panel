<?php

declare(strict_types=1);

namespace Maatify\BehaviorTrace\Recorder;

use Maatify\BehaviorTrace\Contract\BehaviorTracePolicyInterface;
use Maatify\BehaviorTrace\Enum\BehaviorTraceActorTypeEnum;
use Maatify\BehaviorTrace\Enum\BehaviorTraceActorTypeInterface;

class BehaviorTraceDefaultPolicy implements BehaviorTracePolicyInterface
{
    private const MAX_ACTOR_TYPE_LENGTH = 32;

    public function normalizeActorType(string|BehaviorTraceActorTypeInterface $actorType): BehaviorTraceActorTypeInterface
    {
        if ($actorType instanceof BehaviorTraceActorTypeInterface) {
            $value = $actorType->value();
        } else {
            $value = $actorType;
        }

        // 1. Enforce Uppercase
        $value = strtoupper($value);

        // 2. Sanitize characters: Replace anything NOT in [A-Z0-9_.:-] with '_'
        $value = (string)preg_replace('/[^A-Z0-9_.:-]/', '_', $value);

        // 3. Fallback for empty value
        if ($value === '') {
            return BehaviorTraceActorTypeEnum::ANONYMOUS;
        }

        // 4. Enforce Length (Max 32)
        if (strlen($value) > self::MAX_ACTOR_TYPE_LENGTH) {
            $value = substr($value, 0, self::MAX_ACTOR_TYPE_LENGTH);
        }

        // 5. Return Enum if exists
        $enum = BehaviorTraceActorTypeEnum::tryFrom($value);
        if ($enum) {
            return $enum;
        }

        // 6. Return Ad-hoc Implementation
        return new class($value) implements BehaviorTraceActorTypeInterface {
            public function __construct(private readonly string $val) {}
            public function value(): string { return $this->val; }
        };
    }

    public function validateMetadataSize(string $json): bool
    {
        return strlen($json) <= 65536;
    }
}
