<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-10-01 00:00
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\I18n\Reader;

use Maatify\AdminKernel\Domain\Exception\EntityNotFoundException;
use Maatify\AdminKernel\Domain\I18n\ScopeDomains\DTO\I18nScopeDomainListItemDTO;
use Maatify\AdminKernel\Domain\I18n\ScopeDomains\DTO\I18nScopeDomainsDropdownResponseDTO;
use Maatify\AdminKernel\Domain\I18n\ScopeDomains\DTO\I18nScopeDomainsListItemDTO;
use Maatify\AdminKernel\Domain\I18n\ScopeDomains\DTO\I18nScopeDomainsListResponseDTO;
use Maatify\AdminKernel\Domain\I18n\ScopeDomains\I18nScopeDomainsListReaderInterface;
use Maatify\AdminKernel\Domain\I18n\ScopeDomains\I18nScopeDomainsQueryReaderInterface;
use Maatify\AdminKernel\Domain\List\Filters\ResolvedListFilters;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use Maatify\I18n\Exception\ScopeNotFoundException;
use Maatify\I18n\Management\Criteria\ScopeDomainListCriteria;
use Maatify\I18n\Management\Service\I18nManagementReadService;

/**
 * Admin scope <-> domain read side, served by the I18n package's public API.
 */
final readonly class PackageI18nScopeDomainsReader implements
    I18nScopeDomainsListReaderInterface,
    I18nScopeDomainsQueryReaderInterface
{
    public function __construct(
        private I18nManagementReadService $management,
    ) {
    }

    public function listByScopeId(int $scopeId): I18nScopeDomainsDropdownResponseDTO
    {
        try {
            $scope = $this->management->getScope($scopeId);
        } catch (ScopeNotFoundException) {
            throw new EntityNotFoundException('scope not found', 'scopeId');
        }

        $items = [];
        foreach ($this->management->listDomainOptionsForScope($scope->code) as $option) {
            $items[] = new I18nScopeDomainListItemDTO($option->code, $option->name);
        }

        return new I18nScopeDomainsDropdownResponseDTO($items);
    }

    public function queryScopeDomains(
        string $scopeCode,
        ListQueryDTO $query,
        ResolvedListFilters $filters
    ): I18nScopeDomainsListResponseDTO {
        $result = $this->management->searchScopeDomains(new ScopeDomainListCriteria(
            scopeCode: $scopeCode,
            globalSearch: $filters->globalSearch,
            id: PackageListMapper::int($filters, 'id'),
            code: PackageListMapper::string($filters, 'code'),
            name: PackageListMapper::string($filters, 'name'),
            isActive: PackageListMapper::flag($filters, 'is_active'),
            assigned: PackageListMapper::flag($filters, 'assigned'),
            page: PackageListMapper::page($query),
        ));

        $items = [];
        foreach ($result->data as $domain) {
            $items[] = new I18nScopeDomainsListItemDTO(
                id: $domain->id,
                code: $domain->code,
                name: $domain->name,
                description: $domain->description ?? '',
                is_active: $domain->isActive ? 1 : 0,
                sort_order: $domain->sortOrder,
                assigned: $domain->assigned ? 1 : 0,
            );
        }

        return new I18nScopeDomainsListResponseDTO($items, PackageListMapper::pagination($result));
    }
}
