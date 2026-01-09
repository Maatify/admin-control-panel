<?php

declare(strict_types=1);

namespace App\Domain\DTO\AdminList;

use App\Domain\DTO\Common\PaginationDTO;
use JsonSerializable;

readonly class AdminListResponseDTO implements JsonSerializable
{
    /**
     * @param AdminListItemDTO[] $data
     * @param PaginationDTO $pagination
     */
    public function __construct(
        public array $data,
        public PaginationDTO $pagination
    ) {
    }

    /**
     * @return array{data: AdminListItemDTO[], pagination: PaginationDTO}
     */
    public function jsonSerialize(): array
    {
        return [
            'data' => $this->data,
            'pagination' => $this->pagination,
        ];
    }
}
