<?php

declare(strict_types=1);

namespace App\Modules\DiagnosticsTelemetry\Recorder;

use App\Modules\DiagnosticsTelemetry\Enum\DiagnosticsTelemetryActorTypeEnum;
use App\Modules\DiagnosticsTelemetry\Enum\DiagnosticsTelemetryActorTypeInterface;

class DiagnosticsTelemetryDefaultPolicy
{
    private const MAX_ACTOR_TYPE_LENGTH = 32;

    public function normalizeActorType(string|DiagnosticsTelemetryActorTypeInterface $actorType): DiagnosticsTelemetryActorTypeInterface
    {
        if ($actorType instanceof DiagnosticsTelemetryActorTypeInterface) {
            $value = $actorType->value();
        } else {
            $value = $actorType;
        }

        // Basic validation: length
        if (strlen($value) > self::MAX_ACTOR_TYPE_LENGTH) {
            // Trim or throw? Policy says "normalized + validated".
            // "Must be normalized + validated by policy (length <= 32, pattern-based, uppercase recommended)."
            // Let's trim and uppercase.
            $value = substr(strtoupper($value), 0, self::MAX_ACTOR_TYPE_LENGTH);
        } else {
            $value = strtoupper($value);
        }

        // Pattern validation can be added here. For now, strictly simple characters.
        // Assuming strictly alphanumeric + underscores for safety, but docs say "pattern-based".
        if (!preg_match('/^[A-Z0-9_]+$/', $value)) {
             // Fallback to SYSTEM or throw? Best effort means we shouldn't crash.
             // But if invalid actor type, maybe ANONYMOUS?
             // Let's return ANONYMOUS if invalid pattern.
             return DiagnosticsTelemetryActorTypeEnum::ANONYMOUS;
        }

        // Return a simple anonymous class implementation or match existing enum?
        // If it matches a known enum, return it.
        $enum = DiagnosticsTelemetryActorTypeEnum::tryFrom($value);
        if ($enum) {
            return $enum;
        }

        // Otherwise return ad-hoc implementation
        return new class($value) implements DiagnosticsTelemetryActorTypeInterface {
            public function __construct(private readonly string $val) {}
            public function value(): string { return $this->val; }
        };
    }

    public function validateMetadataSize(string $json): bool
    {
        return strlen($json) <= 65536;
    }
}
