<?php

declare(strict_types=1);

namespace Maatify\ImageProfile\DTO;

use JsonSerializable;

final class PaginationDTO implements JsonSerializable
{
    public function __construct(
        public readonly int $page,
        public readonly int $perPage,
        public readonly int $total,
        public readonly int $filtered,
    ) {}

    /**
     * @return array{page: int, per_page: int, total: int, filtered: int}
     */
    public function jsonSerialize(): array
    {
        return [
            'page' => $this->page,
            'per_page' => $this->perPage,
            'total' => $this->total,
            'filtered' => $this->filtered,
        ];
    }
}
