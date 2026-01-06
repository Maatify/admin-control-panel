<?php

declare(strict_types=1);

namespace App\Domain\DTO\Session;

use JsonSerializable;

class SessionListResponseDTO implements JsonSerializable
{
    /**
     * @param SessionListItemDTO[] $data
     * @param array{page: int, per_page: int, total: int} $pagination
     */
    public function __construct(
        public array $data,
        public array $pagination
    ) {
    }

    /**
     * @return array{data: SessionListItemDTO[], pagination: array{page: int, per_page: int, total: int}}
     */
    public function jsonSerialize(): array
    {
        return [
            'data' => $this->data,
            'pagination' => $this->pagination,
        ];
    }
}
