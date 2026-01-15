<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-15 13:08
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Modules\Telemetry\Contracts;

use App\Modules\Telemetry\DTO\TelemetryEventDTO;
use App\Modules\Telemetry\Exceptions\TelemetryStorageException;

/**
 * Module-level logger contract (storage adapter).
 *
 * - Implementations may throw TelemetryStorageException.
 * - Swallowing failures is NOT allowed here.
 */
interface TelemetryLoggerInterface
{
    /**
     * @throws TelemetryStorageException
     */
    public function insert(TelemetryEventDTO $dto): void;
}
