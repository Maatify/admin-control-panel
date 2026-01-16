<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-16 11:32
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Modules\Telemetry\Contracts;

use App\Modules\Telemetry\DTO\TelemetryTraceReadPageDTO;
use App\Modules\Telemetry\DTO\TelemetryTraceReadQueryDTO;

interface TelemetryTraceReaderInterface
{
    public function paginate(TelemetryTraceReadQueryDTO $query): TelemetryTraceReadPageDTO;

    public function count(TelemetryTraceReadQueryDTO $query): int;
}
