<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\DTO\Session;

use Maatify\AdminKernel\Domain\DTO\Common\PaginationDTO;
use JsonSerializable;

class SessionListResponseDTO implements JsonSerializable
{
    /**
     * @param SessionListItemDTO[] $data
     * @param PaginationDTO $pagination
     */
    public function __construct(
        public array $data,
        public PaginationDTO $pagination
    ) {
    }

    /**
     * @return array{data: SessionListItemDTO[], pagination: PaginationDTO}
     */
    public function jsonSerialize(): array
    {
        return [
            'data' => $this->data,
            'pagination' => $this->pagination,
        ];
    }
}
