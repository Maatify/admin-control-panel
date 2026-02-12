<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\LanguageTranslationValue\DTO;

use JsonSerializable;
use Maatify\AdminKernel\Domain\DTO\Common\PaginationDTO;

/**
 * @phpstan-type TranslationValueListResponseArray array{
 *   data: LanguageTranslationValueListItemDTO[],
 *   pagination: PaginationDTO
 * }
 */
final readonly class LanguageTranslationValueListResponseDTO implements JsonSerializable
{
    /**
     * @param LanguageTranslationValueListItemDTO[]  $data
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
