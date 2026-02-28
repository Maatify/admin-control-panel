<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\Language\DTO;

use JsonSerializable;
use Maatify\AdminKernel\Domain\DTO\Common\PaginationDTO;

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
