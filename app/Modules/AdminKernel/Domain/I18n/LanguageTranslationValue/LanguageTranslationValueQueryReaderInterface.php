<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\LanguageTranslationValue;

use Maatify\AdminKernel\Domain\I18n\LanguageTranslationValue\DTO\LanguageTranslationValueListResponseDTO;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use Maatify\AdminKernel\Infrastructure\Query\ResolvedListFilters;

interface LanguageTranslationValueQueryReaderInterface
{
    public function queryTranslationValues(
        int $languageId,
        ListQueryDTO $query,
        ResolvedListFilters $filters
    ): LanguageTranslationValueListResponseDTO;
}
