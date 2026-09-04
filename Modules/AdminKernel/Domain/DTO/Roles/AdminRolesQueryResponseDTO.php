<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\DTO\Roles;

use JsonSerializable;
use Maatify\AdminKernel\Domain\DTO\Common\PaginationDTO;

final readonly class AdminRolesQueryResponseDTO implements JsonSerializable
{
    /**
     * @param AdminRoleListItemDTO[] $data
     */
    public function __construct(
        private array $data,
        private PaginationDTO $pagination
    ) {
    }

    /**
     * @return array{
     *   data: AdminRoleListItemDTO[],
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
