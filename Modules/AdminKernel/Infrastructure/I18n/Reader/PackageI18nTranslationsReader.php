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

use Maatify\AdminKernel\Domain\I18n\Language\DTO\LanguageListItemDTO;
use Maatify\AdminKernel\Domain\I18n\Language\LanguageLookupInterface;
use Maatify\AdminKernel\Domain\I18n\Translations\DTO\I18nScopeDomainKeysSummaryListItemDTO;
use Maatify\AdminKernel\Domain\I18n\Translations\DTO\I18nScopeDomainKeysSummaryListResponseDTO;
use Maatify\AdminKernel\Domain\I18n\Translations\DTO\I18nScopeDomainTranslationsListItemDTO;
use Maatify\AdminKernel\Domain\I18n\Translations\DTO\I18nScopeDomainTranslationsListResponseDTO;
use Maatify\AdminKernel\Domain\I18n\Translations\I18nScopeDomainKeysSummaryQueryReaderInterface;
use Maatify\AdminKernel\Domain\I18n\Translations\I18nScopeDomainTranslationsQueryReaderInterface;
use Maatify\AdminKernel\Domain\List\Filters\ResolvedListFilters;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use Maatify\I18n\Management\Criteria\DomainKeySummaryCriteria;
use Maatify\I18n\Management\Criteria\DomainTranslationGridCriteria;
use Maatify\I18n\Management\Service\I18nManagementReadService;

/**
 * Admin (scope, domain) translation screens (ADR-019 composition).
 *
 * The Host owns the language universe: it decides WHICH languages are measured
 * (all, one by Host id, active only) and passes their exact codes to I18n.
 * I18n counts exact matches and returns keys / values by code; the Host then
 * attaches its own language metadata (id, name, icon, direction) to each row.
 * No Host language table is joined with I18n tables.
 */
final readonly class PackageI18nTranslationsReader implements
    I18nScopeDomainKeysSummaryQueryReaderInterface,
    I18nScopeDomainTranslationsQueryReaderInterface
{
    public function __construct(
        private I18nManagementReadService $management,
        private LanguageLookupInterface $languages,
    ) {
    }

    public function query(
        string $scopeCode,
        string $domainCode,
        ListQueryDTO $query,
        ResolvedListFilters $filters
    ): I18nScopeDomainKeysSummaryListResponseDTO {
        $measured = $this->languages->listAll();

        $languageId = PackageListMapper::int($filters, 'language_id');
        if ($languageId !== null && $languageId > 0) {
            $measured = array_values(array_filter(
                $measured,
                static fn (LanguageListItemDTO $l): bool => $l->id === $languageId
            ));
        }

        // Explicit-only: sent as 1 => active languages only; 0 => all.
        if (PackageListMapper::int($filters, 'language_is_active') === 1) {
            $measured = array_values(array_filter(
                $measured,
                static fn (LanguageListItemDTO $l): bool => $l->isActive
            ));
        }

        $result = $this->management->pageDomainKeySummaries(new DomainKeySummaryCriteria(
            scopeCode: $scopeCode,
            domainCode: $domainCode,
            languageCodes: self::codes($measured),
            globalSearch: $filters->globalSearch,
            keyId: PackageListMapper::int($filters, 'key_id'),
            keyPart: PackageListMapper::string($filters, 'key_part'),
            onlyMissing: PackageListMapper::int($filters, 'missing') === 1,
            page: PackageListMapper::page($query),
        ));

        $items = [];
        foreach ($result->data as $row) {
            $items[] = new I18nScopeDomainKeysSummaryListItemDTO(
                id: $row->id,
                keyPart: $row->keyPart,
                description: $row->description,
                totalLanguages: $row->totalLanguages,
                missingCount: $row->missingCount,
            );
        }

        return new I18nScopeDomainKeysSummaryListResponseDTO($items, PackageListMapper::pagination($result));
    }

    public function queryScopeDomainTranslations(
        string $scopeCode,
        string $domainCode,
        ListQueryDTO $query,
        ResolvedListFilters $filters
    ): I18nScopeDomainTranslationsListResponseDTO {
        $all = $this->languages->listAll();
        $listed = $all;

        $languageId = PackageListMapper::int($filters, 'language_id');
        if ($languageId !== null) {
            $listed = array_values(array_filter(
                $all,
                static fn (LanguageListItemDTO $l): bool => $l->id === $languageId
            ));
        }

        // Free text also matches Host language metadata (the name is Host-owned):
        // the Host translates it into the exact codes it matched.
        $matchedByName = [];
        if ($filters->globalSearch !== null && $filters->globalSearch !== '') {
            foreach ($listed as $language) {
                if (mb_stripos($language->name, $filters->globalSearch) !== false) {
                    $matchedByName[] = $language->code;
                }
            }
        }

        $result = $this->management->pageDomainTranslationGrid(new DomainTranslationGridCriteria(
            scopeCode: $scopeCode,
            domainCode: $domainCode,
            languageCodes: self::codes($listed),
            globalSearch: $filters->globalSearch,
            globalSearchLanguageCodes: $matchedByName,
            keyId: PackageListMapper::int($filters, 'key_id'),
            keyPartLike: PackageListMapper::string($filters, 'key_part'),
            valueLike: PackageListMapper::string($filters, 'value'),
            page: PackageListMapper::page($query),
        ));

        $byCode = [];
        foreach ($listed as $language) {
            $byCode[$language->code] = $language;
        }

        $items = [];
        foreach ($result->data as $row) {
            $language = $byCode[$row->languageCode] ?? null;

            $items[] = new I18nScopeDomainTranslationsListItemDTO(
                id: $row->translationId,
                keyId: $row->keyId,
                keyPart: $row->keyPart,
                description: $row->description,
                languageId: $language?->id,
                languageCode: $row->languageCode,
                languageName: $language?->name,
                languageIcon: $language?->icon,
                languageDirection: $language?->direction->value,
                value: $row->value,
            );
        }

        return new I18nScopeDomainTranslationsListResponseDTO($items, PackageListMapper::pagination($result));
    }

    /**
     * @param list<LanguageListItemDTO> $languages
     *
     * @return list<string>
     */
    private static function codes(array $languages): array
    {
        $codes = [];
        foreach ($languages as $language) {
            $codes[] = $language->code;
        }

        return $codes;
    }
}
