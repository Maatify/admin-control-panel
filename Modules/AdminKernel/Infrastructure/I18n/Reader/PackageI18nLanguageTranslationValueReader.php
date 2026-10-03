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
use Maatify\AdminKernel\Domain\I18n\Language\LanguageLookupInterface;
use Maatify\AdminKernel\Domain\I18n\LanguageTranslationValue\DTO\LanguageTranslationValueListItemDTO;
use Maatify\AdminKernel\Domain\I18n\LanguageTranslationValue\DTO\LanguageTranslationValueListResponseDTO;
use Maatify\AdminKernel\Domain\I18n\LanguageTranslationValue\LanguageTranslationValueQueryReaderInterface;
use Maatify\AdminKernel\Domain\List\Filters\ResolvedListFilters;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use Maatify\I18n\Management\Criteria\LanguageTranslationValuesCriteria;
use Maatify\I18n\Management\Service\I18nManagementReadService;

/**
 * Admin per-language translation values (ADR-019): the Host resolves the Admin
 * route language ID to the exact code; the ID never crosses into I18n.
 */
final readonly class PackageI18nLanguageTranslationValueReader implements LanguageTranslationValueQueryReaderInterface
{
    public function __construct(
        private I18nManagementReadService $management,
        private LanguageLookupInterface $languages,
    ) {
    }

    public function queryTranslationValues(
        int $languageId,
        ListQueryDTO $query,
        ResolvedListFilters $filters
    ): LanguageTranslationValueListResponseDTO {
        $language = $this->languages->getById($languageId);

        if ($language === null) {
            throw new EntityNotFoundException('Language', $languageId);
        }

        $result = $this->management->pageLanguageTranslationValues(new LanguageTranslationValuesCriteria(
            languageCode: $language->code,
            globalSearch: $filters->globalSearch,
            id: PackageListMapper::int($filters, 'id'),
            scopeLike: PackageListMapper::string($filters, 'scope'),
            domainLike: PackageListMapper::string($filters, 'domain'),
            keyPartLike: PackageListMapper::string($filters, 'key_part'),
            valueLike: PackageListMapper::string($filters, 'value'),
            page: PackageListMapper::page($query),
        ));

        $items = [];
        foreach ($result->data as $row) {
            $items[] = new LanguageTranslationValueListItemDTO(
                keyId: $row->keyId,
                scope: $row->scope,
                domain: $row->domain,
                keyPart: $row->keyPart,
                translationId: $row->translationId,
                languageId: $languageId,
                value: $row->value,
                createdAt: $row->createdAt,
                updatedAt: $row->updatedAt,
            );
        }

        return new LanguageTranslationValueListResponseDTO($items, PackageListMapper::pagination($result));
    }
}
