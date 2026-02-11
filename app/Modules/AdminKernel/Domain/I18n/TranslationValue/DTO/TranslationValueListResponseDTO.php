<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\TranslationValue\DTO;

use JsonSerializable;
use Maatify\AdminKernel\Domain\DTO\Common\PaginationDTO;

/**
 * @phpstan-type TranslationValueListResponseArray array{
 *   data: TranslationValueListItemDTO[],
 *   pagination: PaginationDTO
 * }
 */
final readonly class TranslationValueListResponseDTO implements JsonSerializable
{
    /**
     * @param TranslationValueListItemDTO[] $data
     */
    public function __construct(
        public array $data,
        public PaginationDTO $pagination
    ) {}

    /**
     * @return TranslationValueListResponseArray
     */
    public function jsonSerialize(): array
    {
        return [
            'data' => $this->data,
            'pagination' => $this->pagination,
        ];
    }
}
