<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/i18n
 * @Project     maatify:i18n
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-04 01:35
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/i18n view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\I18n\Service;

use Maatify\I18n\Contract\LanguageRepositoryInterface;
use Maatify\I18n\Contract\TranslationKeyRepositoryInterface;
use Maatify\I18n\Contract\TranslationRepositoryInterface;

final readonly class TranslationReadService
{
    public function __construct(
        private LanguageRepositoryInterface $languageRepository,
        private TranslationKeyRepositoryInterface $keyRepository,
        private TranslationRepositoryInterface $translationRepository
    ) {
    }

    /**
     * Safe read:
     * - No exceptions
     * - No parsing
     * - Structured key only
     * - Returns null if nothing resolvable
     */
    public function getValue(
        string $languageCode,
        string $scope,
        string $domain,
        string $key
    ): ?string {
        $language = $this->languageRepository->getByCode($languageCode);
        if ($language === null) {
            return null;
        }

        $translationKey = $this->keyRepository
            ->getByStructuredKey($scope, $domain, $key);

        if ($translationKey === null) {
            return null;
        }

        $value = $this->getDirectValue($language->id, $translationKey->id);
        if ($value !== null) {
            return $value;
        }

        // Fallback chain (one level only by design)
        if ($language->fallbackLanguageId !== null) {
            return $this->getDirectValue(
                $language->fallbackLanguageId,
                $translationKey->id
            );
        }

        return null;
    }

    private function getDirectValue(int $languageId, int $keyId): ?string
    {
        $translation = $this->translationRepository
            ->getByLanguageAndKey($languageId, $keyId);

        return $translation?->value;
    }
}
