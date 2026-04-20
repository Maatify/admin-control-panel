<?php

declare(strict_types=1);

namespace Maatify\WebsiteUiTheme\DTO;

use JsonSerializable;

final readonly class PaginationDTO implements JsonSerializable
{
    public function __construct(
        public int $page,
        public int $perPage,
        public int $total,
        public int $filtered,
    ) {}

    /** @return array{page:int,per_page:int,total:int,filtered:int} */
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
