<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\Language;

use Maatify\AdminKernel\Domain\I18n\Language\DTO\LanguageListResponseDTO;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use Maatify\AdminKernel\Infrastructure\Query\ResolvedListFilters;

interface LanguageQueryReaderInterface
{
    public function queryLanguages(
        ListQueryDTO $query,
        ResolvedListFilters $filters
    ): LanguageListResponseDTO;
}
