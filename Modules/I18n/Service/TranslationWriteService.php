<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/i18n
 * @Project     maatify:i18n
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-04 01:34
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/i18n view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\I18n\Service;

use Maatify\I18n\Contract\LanguageRepositoryInterface;
use Maatify\I18n\Contract\TranslationKeyRepositoryInterface;
use Maatify\I18n\Contract\TranslationRepositoryInterface;
use RuntimeException;

final readonly class TranslationWriteService
{
    public function __construct(
        private LanguageRepositoryInterface $languageRepository,
        private TranslationKeyRepositoryInterface $keyRepository,
        private TranslationRepositoryInterface $translationRepository,
        private I18nGovernancePolicyService $governancePolicy
    ) {
    }

    public function createKey(
        string $scope,
        string $domain,
        string $key,
        ?string $description = null
    ): int {
        $this->governancePolicy
            ->assertScopeAndDomainAllowed($scope, $domain);

        if ($this->keyRepository
                ->getByStructuredKey($scope, $domain, $key) !== null) {
            throw new RuntimeException('Translation key already exists.');
        }

        $id = $this->keyRepository->create(
            scope: $scope,
            domain: $domain,
            key: $key,
            description: $description
        );

        if ($id <= 0) {
            throw new RuntimeException('Failed to create translation key.');
        }

        return $id;
    }

    public function renameKey(
        int $keyId,
        string $scope,
        string $domain,
        string $key
    ): void {
        if ($this->keyRepository->getById($keyId) === null) {
            throw new RuntimeException('Translation key not found.');
        }

        $this->governancePolicy
            ->assertScopeAndDomainAllowed($scope, $domain);

        $existing = $this->keyRepository
            ->getByStructuredKey($scope, $domain, $key);

        if ($existing !== null && $existing->id !== $keyId) {
            throw new RuntimeException('Target key already exists.');
        }

        $this->keyRepository->rename(
            id: $keyId,
            scope: $scope,
            domain: $domain,
            key: $key
        );
    }

    public function updateKeyDescription(int $keyId, string $description): void
    {
        if ($this->keyRepository->getById($keyId) === null) {
            throw new RuntimeException('Translation key not found.');
        }

        $this->keyRepository->updateDescription($keyId, $description);
    }

    public function upsertTranslation(
        int $languageId,
        int $keyId,
        string $value
    ): int {
        if ($this->languageRepository->getById($languageId) === null) {
            throw new RuntimeException('Language not found.');
        }

        if ($this->keyRepository->getById($keyId) === null) {
            throw new RuntimeException('Translation key not found.');
        }

        return $this->translationRepository->upsert(
            $languageId,
            $keyId,
            $value
        );
    }

    public function deleteTranslation(int $languageId, int $keyId): void
    {
        // No logic here – delegate to repository
        $this->translationRepository->deleteByLanguageAndKey(
            languageId: $languageId,
            keyId: $keyId
        );
    }
}
