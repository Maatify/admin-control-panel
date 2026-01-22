<?php

declare(strict_types=1);

namespace App\Modules\TelemetryLogging\Contract;

use App\Modules\TelemetryLogging\DTO\TelemetryCursorDTO;
use App\Modules\TelemetryLogging\DTO\TelemetryEventDTO;

interface TelemetryReaderInterface
{
    /**
     * @param TelemetryCursorDTO|null $cursor
     * @param int $limit
     * @return iterable<TelemetryEventDTO>
     */
    public function read(?TelemetryCursorDTO $cursor, int $limit = 100): iterable;
}
