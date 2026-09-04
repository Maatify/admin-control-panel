<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-26 20:21
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\DTO\Roles;

use Maatify\AdminKernel\Domain\DTO\Common\PaginationDTO;
use JsonSerializable;

class RolesQueryResponseDTO implements JsonSerializable
{
    /** @var RolesListItemDTO[] */
    public array $data;

    public PaginationDTO $pagination;

    /**
     * @param RolesListItemDTO[] $data
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
     *   data: RolesListItemDTO[],
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