<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-16 13:27
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Domain\Telemetry\Contracts;

use App\Domain\DTO\Common\PaginationDTO;
use App\Domain\List\ListQueryDTO;
use App\Domain\Telemetry\DTO\TelemetryListResponseDTO;
use App\Infrastructure\Query\ResolvedListFilters;

interface TelemetryListReaderInterface
{
    public function getTelemetry(
        ListQueryDTO $query,
        ResolvedListFilters $filters
    ): TelemetryListResponseDTO;
}
