<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-15 13:10
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Domain\Telemetry\Recorder;

use App\Domain\Telemetry\DTO\TelemetryRecordDTO;

/**
 * Domain recorder contract.
 *
 * MUST:
 * - enforce best-effort (silence policy)
 * - never throw to callers
 */
interface TelemetryRecorderInterface
{
    public function record(TelemetryRecordDTO $dto): void;
}
