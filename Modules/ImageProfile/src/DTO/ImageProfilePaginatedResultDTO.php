<?php

declare(strict_types=1);

namespace Maatify\ImageProfile\DTO;

use JsonSerializable;

final class ImageProfilePaginatedResultDTO implements JsonSerializable
{
    /**
     * @param list<ImageProfileDTO> $data
     */
    public function __construct(
        public readonly array $data,
        public readonly PaginationDTO $pagination,
    ) {}

    /**
     * @return array{data: list<array<string,mixed>>, pagination: array{page: int, per_page: int, total: int, filtered: int}}
     */
    public function jsonSerialize(): array
    {
        return [
            'data' => array_map(
                static fn (ImageProfileDTO $item): array => $item->jsonSerialize(),
                $this->data,
            ),
            'pagination' => $this->pagination->jsonSerialize(),
        ];
    }
}
