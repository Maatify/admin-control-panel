<?php

declare(strict_types=1);

namespace App\Modules\DiagnosticsTelemetry\Contract;

use App\Modules\DiagnosticsTelemetry\DTO\DiagnosticsTelemetryEventDTO;

interface DiagnosticsTelemetryLoggerInterface
{
    public function write(DiagnosticsTelemetryEventDTO $dto): void;
}
