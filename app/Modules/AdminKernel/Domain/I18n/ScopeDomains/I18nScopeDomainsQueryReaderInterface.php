<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-08 17:40
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\ScopeDomains;

use Maatify\AdminKernel\Domain\DTO\I18n\ScopeDomains\I18nScopeDomainsListResponseDTO;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use Maatify\AdminKernel\Infrastructure\Query\ResolvedListFilters;

interface I18nScopeDomainsQueryReaderInterface
{
    public function queryScopeDomains(
        string $scopeCode,
        ListQueryDTO $query,
        ResolvedListFilters $filters
    ): I18nScopeDomainsListResponseDTO;
}
