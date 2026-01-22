<?php

declare(strict_types=1);

namespace App\Modules\TelemetryLogging\Contract;

use App\Modules\TelemetryLogging\DTO\TelemetryEventDTO;

interface TelemetryWriterInterface
{
    public function write(TelemetryEventDTO $dto): void;
}
