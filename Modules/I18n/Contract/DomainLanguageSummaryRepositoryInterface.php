<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/i18n
 * @Project     maatify:i18n
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-12 20:52
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/i18n view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\I18n\Contract;

interface DomainLanguageSummaryRepositoryInterface
{
    public function incrementTotalKeys(string $scope, string $domain): void;

    public function decrementTotalKeys(string $scope, string $domain): void;

    public function incrementTranslated(
        string $scope,
        string $domain,
        int $languageId
    ): void;

    public function decrementTranslated(
        string $scope,
        string $domain,
        int $languageId
    ): void;

    public function truncate(): void;

    public function insertRow(
        string $scope,
        string $domain,
        int $languageId,
        int $totalKeys,
        int $translatedCount
    ): void;

}
