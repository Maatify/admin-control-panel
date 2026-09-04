<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\DTO\Permissions;

use JsonSerializable;
use Maatify\AdminKernel\Domain\DTO\Common\PaginationDTO;

final readonly class DirectPermissionsAssignableQueryResponseDTO implements JsonSerializable
{
    /**
     * @param DirectPermissionsAssignableListItemDTO[] $data
     */
    public function __construct(
        private array $data,
        private PaginationDTO $pagination
    ) {}

    /**
     * @return array<string, mixed>
     *
     * @phpstan-return array{
     *   data: DirectPermissionsAssignableListItemDTO[],
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
