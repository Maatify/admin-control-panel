<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\DTO\Permissions;

use JsonSerializable;
use Maatify\AdminKernel\Domain\DTO\Common\PaginationDTO;

final readonly class EffectivePermissionsQueryResponseDTO implements JsonSerializable
{
    /**
     * @param EffectivePermissionListItemDTO[] $data
     */
    public function __construct(
        private array $data,
        private PaginationDTO $pagination
    ) {
    }

    /**
     * @return array{
     *   data: EffectivePermissionListItemDTO[],
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
