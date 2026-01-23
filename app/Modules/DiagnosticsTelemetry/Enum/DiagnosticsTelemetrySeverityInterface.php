<?php

declare(strict_types=1);

namespace App\Modules\DiagnosticsTelemetry\Enum;

interface DiagnosticsTelemetrySeverityInterface
{
    public function value(): string;
}
