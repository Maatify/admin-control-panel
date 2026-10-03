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
use Maatify\AdminKernel\Domain\I18n\Domain\DTO\I18nDomainDetailsDTO;
use Maatify\AdminKernel\Domain\I18n\Domain\DTO\I18nDomainsListItemDTO;
use Maatify\AdminKernel\Domain\I18n\Domain\DTO\I18nDomainsListResponseDTO;
use Maatify\AdminKernel\Domain\I18n\Domain\I18nDomainDetailsReaderInterface;
use Maatify\AdminKernel\Domain\I18n\Domain\I18nDomainsQueryReaderInterface;
use Maatify\AdminKernel\Domain\List\Filters\ResolvedListFilters;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use Maatify\I18n\Exception\DomainNotFoundException;
use Maatify\I18n\Management\Criteria\DomainListCriteria;
use Maatify\I18n\Management\Service\I18nManagementReadService;

/**
 * Admin domain read side, served by the I18n package's public API.
 */
final readonly class PackageI18nDomainReader implements
    I18nDomainsQueryReaderInterface,
    I18nDomainDetailsReaderInterface
{
    public function __construct(
        private I18nManagementReadService $management,
    ) {
    }

    public function queryI18nDomains(
        ListQueryDTO $query,
        ResolvedListFilters $filters
    ): I18nDomainsListResponseDTO {
        $result = $this->management->searchDomains(new DomainListCriteria(
            globalSearch: $filters->globalSearch,
            id: PackageListMapper::int($filters, 'id'),
            code: PackageListMapper::string($filters, 'code'),
            name: PackageListMapper::string($filters, 'name'),
            isActive: PackageListMapper::flag($filters, 'is_active'),
            page: PackageListMapper::page($query),
        ));

        $items = [];
        foreach ($result->data as $domain) {
            $items[] = new I18nDomainsListItemDTO(
                id: $domain->id,
                code: $domain->code,
                name: $domain->name,
                description: $domain->description ?? '',
                is_active: $domain->isActive ? 1 : 0,
                sort_order: $domain->sortOrder,
            );
        }

        return new I18nDomainsListResponseDTO($items, PackageListMapper::pagination($result));
    }

    public function getDomainDetailsById(int $id): I18nDomainDetailsDTO
    {
        try {
            $domain = $this->management->getDomain($id);
        } catch (DomainNotFoundException) {
            throw new EntityNotFoundException('Domain not found', 'domain_id');
        }

        return new I18nDomainDetailsDTO(
            $domain->id,
            $domain->code,
            $domain->name,
            $domain->description ?? '',
            $domain->isActive ? 1 : 0,
            $domain->sortOrder,
        );
    }
}
