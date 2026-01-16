<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-16 13:28
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Domain\Telemetry\DTO;

use App\Domain\DTO\Common\PaginationDTO;

final readonly class TelemetryListResponseDTO
{
    /**
     * @param   array<int, TelemetryViewDTO>  $data
     */
    public function __construct(
        public array $data,
        public PaginationDTO $pagination
    )
    {
    }
}
