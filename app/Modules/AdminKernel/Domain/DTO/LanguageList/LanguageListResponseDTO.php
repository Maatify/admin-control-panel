<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\DTO\LanguageList;

use Maatify\AdminKernel\Domain\DTO\Common\PaginationDTO;
use JsonSerializable;

/**
 * @phpstan-type LanguageListResponseArray array{
 *   data: LanguageListItemDTO[],
 *   pagination: PaginationDTO
 * }
 */
final readonly class LanguageListResponseDTO implements JsonSerializable
{
    /**
     * @param LanguageListItemDTO[] $data
     */
    public function __construct(
        public array $data,
        public PaginationDTO $pagination
    ) {}

    /**
     * @return LanguageListResponseArray
     */
    public function jsonSerialize(): array
    {
        return [
            'data' => $this->data,
            'pagination' => $this->pagination,
        ];
    }
}
