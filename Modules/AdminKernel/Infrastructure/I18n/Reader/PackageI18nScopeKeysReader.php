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
use Maatify\AdminKernel\Domain\I18n\Keys\DTO\I18nScopeKeyListItemDTO;
use Maatify\AdminKernel\Domain\I18n\Keys\DTO\I18nScopeKeysListResponseDTO;
use Maatify\AdminKernel\Domain\I18n\Keys\I18nScopeKeysQueryReaderInterface;
use Maatify\AdminKernel\Domain\List\Filters\ResolvedListFilters;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use Maatify\I18n\Exception\ScopeNotFoundException;
use Maatify\I18n\Management\Criteria\KeyListCriteria;
use Maatify\I18n\Management\Service\I18nManagementReadService;

/**
 * Admin translation-key list, served by the I18n package's public API.
 */
final readonly class PackageI18nScopeKeysReader implements I18nScopeKeysQueryReaderInterface
{
    public function __construct(
        private I18nManagementReadService $management,
    ) {
    }

    public function queryScopeKeys(
        int $scopeId,
        ListQueryDTO $query,
        ResolvedListFilters $filters
    ): I18nScopeKeysListResponseDTO {
        try {
            $scope = $this->management->getScope($scopeId);
        } catch (ScopeNotFoundException) {
            throw new EntityNotFoundException('scope not found', 'scopeId');
        }

        $result = $this->management->searchKeys(new KeyListCriteria(
            scopeCode: $scope->code,
            globalSearch: $filters->globalSearch,
            id: PackageListMapper::int($filters, 'id'),
            domainLike: PackageListMapper::string($filters, 'domain'),
            keyPartLike: PackageListMapper::string($filters, 'key_part'),
            page: PackageListMapper::page($query),
        ));

        $items = [];
        foreach ($result->data as $key) {
            $items[] = new I18nScopeKeyListItemDTO(
                $key->id,
                $key->scope,
                $key->domain,
                $key->key,
                $key->description,
                $key->createdAt
            );
        }

        return new I18nScopeKeysListResponseDTO($items, PackageListMapper::pagination($result));
    }
}
