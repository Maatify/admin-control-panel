<?php

declare(strict_types=1);

namespace App\Modules\DiagnosticsTelemetry\Recorder;

use App\Modules\DiagnosticsTelemetry\Contract\DiagnosticsTelemetryPolicyInterface;
use App\Modules\DiagnosticsTelemetry\Enum\DiagnosticsTelemetryActorTypeEnum;
use App\Modules\DiagnosticsTelemetry\Enum\DiagnosticsTelemetryActorTypeInterface;
use App\Modules\DiagnosticsTelemetry\Enum\DiagnosticsTelemetrySeverityEnum;
use App\Modules\DiagnosticsTelemetry\Enum\DiagnosticsTelemetrySeverityInterface;

class DiagnosticsTelemetryDefaultPolicy implements DiagnosticsTelemetryPolicyInterface
{
    private const MAX_ACTOR_TYPE_LENGTH = 32;
    private const MAX_SEVERITY_LENGTH = 16;

    public function normalizeActorType(string|DiagnosticsTelemetryActorTypeInterface $actorType): DiagnosticsTelemetryActorTypeInterface
    {
        if ($actorType instanceof DiagnosticsTelemetryActorTypeInterface) {
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
            return DiagnosticsTelemetryActorTypeEnum::ANONYMOUS;
        }

        // 4. Enforce Length (Max 32)
        if (strlen($value) > self::MAX_ACTOR_TYPE_LENGTH) {
            $value = substr($value, 0, self::MAX_ACTOR_TYPE_LENGTH);
        }

        // 5. Return Enum if exists
        $enum = DiagnosticsTelemetryActorTypeEnum::tryFrom($value);
        if ($enum) {
            return $enum;
        }

        // 6. Return Ad-hoc Implementation
        return new class($value) implements DiagnosticsTelemetryActorTypeInterface {
            public function __construct(private readonly string $val) {}
            public function value(): string { return $this->val; }
        };
    }

    public function normalizeSeverity(string|DiagnosticsTelemetrySeverityInterface $severity): DiagnosticsTelemetrySeverityInterface
    {
        if ($severity instanceof DiagnosticsTelemetrySeverityInterface) {
            return $severity;
        }

        $value = strtoupper($severity);

        // Enforce Length (Max 16)
        if (strlen($value) > self::MAX_SEVERITY_LENGTH) {
            $value = substr($value, 0, self::MAX_SEVERITY_LENGTH);
        }

        $enum = DiagnosticsTelemetrySeverityEnum::tryFrom($value);
        if ($enum) {
            return $enum;
        }

        // Ad-hoc severity for unknown values from DB or input
        return new class($value) implements DiagnosticsTelemetrySeverityInterface {
            public function __construct(private readonly string $val) {}
            public function value(): string { return $this->val; }
        };
    }

    public function validateMetadataSize(string $json): bool
    {
        return strlen($json) <= 65536;
    }
}
