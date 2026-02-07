<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/i18n
 * @Project     maatify:i18n
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-06 21:01
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/i18n view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\I18n\Service;

use Maatify\I18n\Contract\LanguageRepositoryInterface;
use Maatify\I18n\Contract\TranslationKeyRepositoryInterface;
use Maatify\I18n\Contract\TranslationRepositoryInterface;
use Maatify\I18n\DTO\TranslationDomainValuesDTO;
use Maatify\I18n\Exception\LanguageNotFoundException;

final readonly class TranslationDomainReadService
{
    public function __construct(
        private LanguageRepositoryInterface $languageRepository,
        private TranslationKeyRepositoryInterface $keyRepository,
        private TranslationRepositoryInterface $translationRepository,
        private I18nGovernancePolicyService $policyService
    )
    {
    }

    /**
     * Bulk read for a single domain.
     *
     * - One DB round-trip
     * - Policy enforced
     * - Fallback supported (one level)
     */
    public function getDomainValues(
        string $languageCode,
        string $scope,
        string $domain
    ): TranslationDomainValuesDTO
    {
        // 1) Enforce governance
        if (!$this->policyService->isScopeAndDomainReadable($scope, $domain)) {
            return new TranslationDomainValuesDTO([]);
        }

        // 2) Resolve language
        $language = $this->languageRepository->getByCode($languageCode);
        if ($language === null) {
            throw new LanguageNotFoundException($languageCode);
        }

        // 3) Resolve keys for (scope + domain)
        $keys = $this->keyRepository->listByScopeAndDomain(
            scope : $scope,
            domain: $domain
        );

        if ($keys->isEmpty()) {
            return new TranslationDomainValuesDTO([]);
        }

        $values = [];

        foreach ($keys->items as $keyDto) {
            // Try direct translation
            $translation = $this->translationRepository
                ->getByLanguageAndKey($language->id, $keyDto->id);

            if ($translation !== null) {
                $values[$keyDto->key] = $translation->value;
                continue;
            }

            // Fallback (one level only)
            if ($language->fallbackLanguageId !== null) {
                $fallback = $this->translationRepository
                    ->getByLanguageAndKey(
                        $language->fallbackLanguageId,
                        $keyDto->id
                    );

                if ($fallback !== null) {
                    $values[$keyDto->key] = $fallback->value;
                }
            }
        }

        return new TranslationDomainValuesDTO($values);
    }
}
