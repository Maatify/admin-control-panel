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
use Maatify\I18n\Exception\LanguageNotFoundException;
use Maatify\I18n\Exception\TranslationKeyAlreadyExistsException;
use Maatify\I18n\Exception\TranslationKeyCreateFailedException;
use Maatify\I18n\Exception\TranslationKeyNotFoundException;
use Maatify\I18n\Exception\TranslationUpsertFailedException;
use Maatify\I18n\Exception\TranslationUpdateFailedException;

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
            throw new TranslationKeyAlreadyExistsException(
                $scope,
                $domain,
                $key
            );
        }

        $id = $this->keyRepository->create(
            scope: $scope,
            domain: $domain,
            key: $key,
            description: $description
        );

        if ($id <= 0) {
            throw new TranslationKeyCreateFailedException(
                $scope,
                $domain,
                $key
            );
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
            throw new TranslationKeyNotFoundException($keyId);
        }

        $this->governancePolicy
            ->assertScopeAndDomainAllowed($scope, $domain);

        $existing = $this->keyRepository
            ->getByStructuredKey($scope, $domain, $key);

        if ($existing !== null && $existing->id !== $keyId) {
            throw new TranslationKeyAlreadyExistsException(
                $scope,
                $domain,
                $key
            );
        }

        if (
            !$this->keyRepository->rename(
                id: $keyId,
                scope: $scope,
                domain: $domain,
                key: $key
            )
        ) {
            throw new TranslationUpdateFailedException('key.rename');
        }
    }

    public function updateKeyDescription(
        int $keyId,
        string $description
    ): void {
        if ($this->keyRepository->getById($keyId) === null) {
            throw new TranslationKeyNotFoundException($keyId);
        }

        if (
            !$this->keyRepository->updateDescription(
                $keyId,
                $description
            )
        ) {
            throw new TranslationUpdateFailedException('key.description');
        }
    }

    public function upsertTranslation(
        int $languageId,
        int $keyId,
        string $value
    ): int {
        if ($this->languageRepository->getById($languageId) === null) {
            throw new LanguageNotFoundException($languageId);
        }

        if ($this->keyRepository->getById($keyId) === null) {
            throw new TranslationKeyNotFoundException($keyId);
        }

        $id = $this->translationRepository->upsert(
            $languageId,
            $keyId,
            $value
        );

        if ($id <= 0) {
            throw new TranslationUpsertFailedException(
                $languageId,
                $keyId
            );
        }

        return $id;
    }

    public function deleteTranslation(
        int $languageId,
        int $keyId
    ): void {
        // fail-soft BUT validated
        if ($this->languageRepository->getById($languageId) === null) {
            throw new LanguageNotFoundException($languageId);
        }

        if ($this->keyRepository->getById($keyId) === null) {
            throw new TranslationKeyNotFoundException($keyId);
        }

        $this->translationRepository->deleteByLanguageAndKey(
            languageId: $languageId,
            keyId: $keyId
        );
    }
}
