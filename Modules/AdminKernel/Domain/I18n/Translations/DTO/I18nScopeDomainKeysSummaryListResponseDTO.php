<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\Translations\DTO;

use JsonSerializable;
use Maatify\AdminKernel\Domain\DTO\Common\PaginationDTO;

/**
 * @phpstan-type I18nScopeDomainTranslationsListResponseArray array{
 *   data: I18nScopeDomainKeysSummaryListItemDTO[],
 *   pagination: array{
 *       page: int,
 *       per_page: int,
 *       total: int,
 *       filtered: int
 *   }
 * }
 */
final readonly class I18nScopeDomainKeysSummaryListResponseDTO implements JsonSerializable
{
    /**
     * @param I18nScopeDomainKeysSummaryListItemDTO[]  $data
     */
    public function __construct(
        public array $data,
        public PaginationDTO $pagination,
    ) {}

    /**
     * @return I18nScopeDomainTranslationsListResponseArray
     */
    public function jsonSerialize(): array
    {
        return [
            'data'       => $this->data,
            'pagination' => $this->pagination->jsonSerialize(),
        ];
    }
}
