<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-11 22:26
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\DTO\ActivityLog;

use Maatify\AdminKernel\Domain\DTO\Common\PaginationDTO;
use JsonSerializable;

final class ActivityLogListResponseDTO implements JsonSerializable
{
    /**
     * @param   ActivityLogListItemDTO[]  $data
     */
    public function __construct(
        public array $data,
        public PaginationDTO $pagination
    )
    {
    }

    /**
     * @return array{
     *   data: ActivityLogListItemDTO[],
     *   pagination: PaginationDTO
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'data'       => $this->data,
            'pagination' => $this->pagination,
        ];
    }
}
