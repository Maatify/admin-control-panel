<?php

declare(strict_types=1);

namespace Maatify\DiagnosticsTelemetry\Contract;

use Maatify\DiagnosticsTelemetry\Enum\DiagnosticsTelemetryActorTypeInterface;
use Maatify\DiagnosticsTelemetry\Enum\DiagnosticsTelemetrySeverityInterface;

interface DiagnosticsTelemetryPolicyInterface
{
    public function normalizeActorType(string|DiagnosticsTelemetryActorTypeInterface $actorType): DiagnosticsTelemetryActorTypeInterface;

    public function normalizeSeverity(string|DiagnosticsTelemetrySeverityInterface $severity): DiagnosticsTelemetrySeverityInterface;

    public function validateMetadataSize(string $json): bool;
}
