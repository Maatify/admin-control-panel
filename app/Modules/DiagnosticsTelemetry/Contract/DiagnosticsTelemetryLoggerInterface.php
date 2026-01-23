<?php

declare(strict_types=1);

namespace Maatify\DiagnosticsTelemetry\Contract;

use Maatify\DiagnosticsTelemetry\DTO\DiagnosticsTelemetryEventDTO;

interface DiagnosticsTelemetryLoggerInterface
{
    public function write(DiagnosticsTelemetryEventDTO $dto): void;
}
