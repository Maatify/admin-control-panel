<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\Reader;

use Maatify\AdminKernel\Domain\DTO\LanguageList\LanguageListResponseDTO;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use Maatify\AdminKernel\Infrastructure\Query\ResolvedListFilters;

interface LanguageQueryReaderInterface
{
    public function queryLanguages(
        ListQueryDTO $query,
        ResolvedListFilters $filters
    ): LanguageListResponseDTO;
}
