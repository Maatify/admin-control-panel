<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\Scope\Reader;

use Maatify\AdminKernel\Domain\DTO\I18nScopesList\I18nScopesListResponseDTO;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use Maatify\AdminKernel\Infrastructure\Query\ResolvedListFilters;

interface I18nScopesQueryReaderInterface
{
    public function queryI18nScopes(
        ListQueryDTO $query,
        ResolvedListFilters $filters
    ): I18nScopesListResponseDTO;
}
