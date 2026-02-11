<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\TranslationValue;

use Maatify\AdminKernel\Domain\I18n\TranslationValue\DTO\TranslationValueListResponseDTO;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use Maatify\AdminKernel\Infrastructure\Query\ResolvedListFilters;

interface TranslationValueQueryReaderInterface
{
    public function queryTranslationValues(
        int $languageId,
        ListQueryDTO $query,
        ResolvedListFilters $filters
    ): TranslationValueListResponseDTO;
}
