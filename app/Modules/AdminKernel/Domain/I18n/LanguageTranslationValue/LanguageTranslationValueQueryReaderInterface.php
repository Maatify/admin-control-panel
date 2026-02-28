<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\LanguageTranslationValue;

use Maatify\AdminKernel\Domain\I18n\LanguageTranslationValue\DTO\LanguageTranslationValueListResponseDTO;
use Maatify\AdminKernel\Domain\List\Filters\ResolvedListFilters;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;

interface LanguageTranslationValueQueryReaderInterface
{
    public function queryTranslationValues(
        int $languageId,
        ListQueryDTO $query,
        ResolvedListFilters $filters
    ): LanguageTranslationValueListResponseDTO;
}
