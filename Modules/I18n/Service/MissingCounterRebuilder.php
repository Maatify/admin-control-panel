<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/i18n
 * @Project     maatify:i18n
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-12 20:55
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/i18n view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\I18n\Service;

use Maatify\I18n\Contract\DomainLanguageSummaryRepositoryInterface;
use Maatify\I18n\Contract\TranslationKeyRepositoryInterface;
use Maatify\I18n\Contract\TranslationRepositoryInterface;
use Maatify\LanguageCore\Contract\LanguageRepositoryInterface;

final readonly class MissingCounterRebuilder
{
    public function __construct(
        private DomainLanguageSummaryRepositoryInterface $summaryRepository,
        private TranslationKeyRepositoryInterface $keyRepository,
        private TranslationRepositoryInterface $translationRepository,
        private LanguageRepositoryInterface $languageRepository
    ) {
    }

    public function fullRebuild(): void
    {
        $this->summaryRepository->truncate();

        $languages = $this->languageRepository->listAll();
        $keys = $this->keyRepository->listAll();

        // نجمع keys حسب scope+domain
        $groupedKeys = [];

        foreach ($keys as $key) {
            $groupedKeys[$key->scope][$key->domain][] = $key->id;
        }

        foreach ($groupedKeys as $scope => $domains) {
            foreach ($domains as $domain => $keyIds) {

                $totalKeys = count($keyIds);

                foreach ($languages as $language) {

                    $translatedCount = $this
                        ->translationRepository
                        ->countByLanguageAndKeyIds(
                            $language->id,
                            $keyIds
                        );

                    $this->summaryRepository->insertRow(
                        scope: $scope,
                        domain: $domain,
                        languageId: $language->id,
                        totalKeys: $totalKeys,
                        translatedCount: $translatedCount
                    );
                }
            }
        }
    }
}
