<?php
/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-13 00:40
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\Translations;

use Maatify\AdminKernel\Domain\I18n\Translations\DTO\I18nScopeDomainKeysSummaryListResponseDTO;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use Maatify\AdminKernel\Infrastructure\Query\ResolvedListFilters;

interface I18nScopeDomainKeysSummaryQueryReaderInterface
{
    /**
     * Executes paginated translations query for a specific (scope + domain).
     *
     * @param string $scopeCode   Canonical scope code (e.g. "admin")
     * @param string $domainCode  Canonical domain code (e.g. "auth")
     * @param ListQueryDTO $query Canonical pagination/search DTO
     * @param ResolvedListFilters $filters Resolved filters based on capabilities
     *
     * @return I18nScopeDomainKeysSummaryListResponseDTO
     */
    public function query(
        string $scopeCode,
        string $domainCode,
        ListQueryDTO $query,
        ResolvedListFilters $filters
    ): I18nScopeDomainKeysSummaryListResponseDTO;
}
