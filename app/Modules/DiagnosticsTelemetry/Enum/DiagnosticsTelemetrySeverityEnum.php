<?php

declare(strict_types=1);

namespace Maatify\DiagnosticsTelemetry\Enum;

enum DiagnosticsTelemetrySeverityEnum: string implements DiagnosticsTelemetrySeverityInterface
{
    case INFO = 'INFO';
    case WARNING = 'WARNING';
    case ERROR = 'ERROR';
    case CRITICAL = 'CRITICAL';

    public function value(): string
    {
        return $this->value;
    }
}
