<?php

declare(strict_types=1);

namespace App\Domain\DTO\AdminList;

use JsonSerializable;

readonly class AdminListResponseDTO implements JsonSerializable
{
    /**
     * @param AdminListItemDTO[] $data
     * @param array{page: int, per_page: int, total: int, total_pages: int} $meta
     */
    public function __construct(
        public array $data,
        public array $meta
    ) {
    }

    /**
     * @return array{data: array<array{id: int, email: string, created_at: string}>, meta: array{page: int, per_page: int, total: int, total_pages: int}}
     */
    public function jsonSerialize(): array
    {
        return [
            'data' => array_map(fn(AdminListItemDTO $dto) => $dto->jsonSerialize(), $this->data),
            'meta' => $this->meta,
        ];
    }
}
