<?php

declare(strict_types=1);

namespace App\Modules\DiagnosticsTelemetry\Contract;

use App\Modules\DiagnosticsTelemetry\Enum\DiagnosticsTelemetryActorTypeInterface;
use App\Modules\DiagnosticsTelemetry\Enum\DiagnosticsTelemetrySeverityInterface;

interface DiagnosticsTelemetryPolicyInterface
{
    public function normalizeActorType(string|DiagnosticsTelemetryActorTypeInterface $actorType): DiagnosticsTelemetryActorTypeInterface;

    public function normalizeSeverity(string|DiagnosticsTelemetrySeverityInterface $severity): DiagnosticsTelemetrySeverityInterface;

    public function validateMetadataSize(string $json): bool;
}
