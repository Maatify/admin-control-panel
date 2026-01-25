<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-25 18:57
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Domain\DTO\Permission;

use App\Domain\DTO\Common\PaginationDTO;
use JsonSerializable;

final class PermissionsQueryResponseDTO implements JsonSerializable
{
    /** @var PermissionListItemDTO[] */
    public array $data;

    public PaginationDTO $pagination;

    /**
     * @param PermissionListItemDTO[] $data
     */
    public function __construct(
        array $data,
        PaginationDTO $pagination
    ) {
        $this->data = $data;
        $this->pagination = $pagination;
    }

    /**
     * @return array{
     *   data: PermissionListItemDTO[],
     *   pagination: PaginationDTO
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'data' => $this->data,
            'pagination' => $this->pagination,
        ];
    }
}
