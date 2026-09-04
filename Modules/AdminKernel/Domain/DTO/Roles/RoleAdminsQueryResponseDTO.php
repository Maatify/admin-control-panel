<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\DTO\Roles;

use JsonSerializable;
use Maatify\AdminKernel\Domain\DTO\Common\PaginationDTO;

readonly class RoleAdminsQueryResponseDTO implements JsonSerializable
{
    /**
     * @param RoleAdminListItemDTO[] $data
     * @param PaginationDTO $pagination
     */
    public function __construct(
        public array $data,
        public PaginationDTO $pagination
    ) {
    }

    /**
     * @return array{
     *   data: RoleAdminListItemDTO[],
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
