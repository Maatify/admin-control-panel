<?php

declare(strict_types=1);

namespace Maatify\DiagnosticsTelemetry\Enum;

interface DiagnosticsTelemetrySeverityInterface
{
    public function value(): string;
}
