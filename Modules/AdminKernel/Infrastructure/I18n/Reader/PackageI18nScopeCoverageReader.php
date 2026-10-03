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

use Maatify\AdminKernel\Domain\I18n\Coverage\DTO\ScopeCoverageByDomainItemDTO;
use Maatify\AdminKernel\Domain\I18n\Coverage\DTO\ScopeCoverageByLanguageItemDTO;
use Maatify\AdminKernel\Domain\I18n\Coverage\I18nScopeCoverageReaderInterface;
use Maatify\AdminKernel\Domain\I18n\Language\LanguageLookupInterface;
use Maatify\I18n\Exception\ScopeNotFoundException;
use Maatify\I18n\Management\Service\I18nManagementReadService;
use Maatify\I18n\Management\Service\I18nOperationalReadService;

/**
 * Admin scope coverage (ADR-019 composition).
 *
 * I18n returns exact-code facts; the Host owns the language list and display
 * metadata, so it composes them here. A Host language with no I18n row is
 * translated = 0. There is no SQL JOIN between Host language tables and I18n
 * tables anywhere on this path.
 */
final readonly class PackageI18nScopeCoverageReader implements I18nScopeCoverageReaderInterface
{
    public function __construct(
        private I18nManagementReadService $management,
        private I18nOperationalReadService $operational,
        private LanguageLookupInterface $languages,
    ) {
    }

    public function getScopeCoverageByLanguage(int $scopeId): array
    {
        try {
            $scope = $this->management->getScope($scopeId);
        } catch (ScopeNotFoundException) {
            return [];
        }

        $coverage = $this->operational->scopeKeyCoverage($scope->code);

        $translatedByCode = [];
        foreach ($coverage->translatedByLanguage as $row) {
            if ($row->languageCode !== null) {
                $translatedByCode[$row->languageCode] = $row->count;
            }
        }

        $result = [];
        foreach ($this->languages->listAll() as $language) {
            $translated = $translatedByCode[$language->code] ?? 0;

            $result[] = new ScopeCoverageByLanguageItemDTO(
                languageId: $language->id,
                languageCode: $language->code,
                languageName: $language->name,
                languageIcon: $language->icon,
                totalKeys: $coverage->totalKeys,
                translatedCount: $translated,
                missingCount: $coverage->totalKeys - $translated,
                completionPercent: self::percent($translated, $coverage->totalKeys)
            );
        }

        return $result;
    }

    public function getScopeCoverageByDomain(int $scopeId, int $languageId): array
    {
        try {
            $scope = $this->management->getScope($scopeId);
        } catch (ScopeNotFoundException) {
            return [];
        }

        $language = $this->languages->getById($languageId);
        if ($language === null) {
            return [];
        }

        $result = [];
        foreach ($this->operational->domainCoverage($scope->code, $language->code) as $domain) {
            $result[] = new ScopeCoverageByDomainItemDTO(
                domainId: $domain->domainId,
                domainCode: $domain->domainCode,
                domainName: $domain->domainName,
                totalKeys: $domain->totalKeys,
                translatedCount: $domain->translatedCount,
                missingCount: $domain->totalKeys - $domain->translatedCount,
                completionPercent: self::percent($domain->translatedCount, $domain->totalKeys)
            );
        }

        return $result;
    }

    private static function percent(int $translated, int $total): float
    {
        return $total > 0 ? round(($translated / $total) * 100, 1) : 0.0;
    }
}
