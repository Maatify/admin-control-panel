<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\Scope\Reader;

use Maatify\AdminKernel\Domain\I18n\Scope\DTO\I18nScopesListResponseDTO;
use Maatify\AdminKernel\Domain\List\Filters\ResolvedListFilters;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;

interface I18nScopesQueryReaderInterface
{
    public function queryI18nScopes(
        ListQueryDTO $query,
        ResolvedListFilters $filters
    ): I18nScopesListResponseDTO;
}
