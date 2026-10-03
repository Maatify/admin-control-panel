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
use Maatify\AdminKernel\Domain\I18n\Scope\DTO\I18nScopeDropdownItemDTO;
use Maatify\AdminKernel\Domain\I18n\Scope\DTO\I18nScopeDropdownResponseDTO;
use Maatify\AdminKernel\Domain\I18n\Scope\DTO\I18nScopesListItemDTO;
use Maatify\AdminKernel\Domain\I18n\Scope\DTO\I18nScopesListResponseDTO;
use Maatify\AdminKernel\Domain\I18n\Scope\Reader\I18nScopeDetailsRepositoryInterface;
use Maatify\AdminKernel\Domain\I18n\Scope\Reader\I18nScopeDropdownReaderInterface;
use Maatify\AdminKernel\Domain\I18n\Scope\Reader\I18nScopesQueryReaderInterface;
use Maatify\AdminKernel\Domain\List\Filters\ResolvedListFilters;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use Maatify\I18n\DTO\ScopeDTO;
use Maatify\I18n\Exception\ScopeNotFoundException;
use Maatify\I18n\Management\Criteria\ScopeListCriteria;
use Maatify\I18n\Management\Service\I18nManagementReadService;
use Maatify\I18n\Management\Service\I18nScopeReadService;

/**
 * Admin scope read side, served by the I18n package's public API. The Host
 * keeps its own response contract (DTOs, pagination envelope); it never reads
 * the package tables.
 */
final readonly class PackageI18nScopeReader implements
    I18nScopesQueryReaderInterface,
    I18nScopeDetailsRepositoryInterface,
    I18nScopeDropdownReaderInterface
{
    public function __construct(
        private I18nManagementReadService $management,
        private I18nScopeReadService $scopes,
    ) {
    }

    public function queryI18nScopes(
        ListQueryDTO $query,
        ResolvedListFilters $filters
    ): I18nScopesListResponseDTO {
        $result = $this->management->searchScopes(new ScopeListCriteria(
            globalSearch: $filters->globalSearch,
            id: PackageListMapper::int($filters, 'id'),
            code: PackageListMapper::string($filters, 'code'),
            name: PackageListMapper::string($filters, 'name'),
            isActive: PackageListMapper::flag($filters, 'is_active'),
            page: PackageListMapper::page($query),
        ));

        $items = [];
        foreach ($result->data as $scope) {
            $items[] = self::item($scope);
        }

        return new I18nScopesListResponseDTO($items, PackageListMapper::pagination($result));
    }

    public function getScopeDetailsById(int $scopeId): I18nScopesListItemDTO
    {
        try {
            return self::item($this->management->getScope($scopeId));
        } catch (ScopeNotFoundException) {
            throw new EntityNotFoundException('scope', $scopeId);
        }
    }

    public function getDropdownList(): I18nScopeDropdownResponseDTO
    {
        $items = [];
        foreach ($this->scopes->listActiveScopes() as $scope) {
            $items[] = new I18nScopeDropdownItemDTO($scope->id, $scope->code, $scope->name);
        }

        return new I18nScopeDropdownResponseDTO($items);
    }

    private static function item(ScopeDTO $scope): I18nScopesListItemDTO
    {
        return new I18nScopesListItemDTO(
            id: $scope->id,
            code: $scope->code,
            name: $scope->name,
            description: $scope->description ?? '',
            is_active: $scope->isActive ? 1 : 0,
            sort_order: $scope->sortOrder,
        );
    }
}
