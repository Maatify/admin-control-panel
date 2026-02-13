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

use Maatify\I18n\Contract\I18nTransactionManagerInterface;
use Maatify\I18n\Contract\TranslationKeyRepositoryInterface;
use Maatify\I18n\Contract\TranslationRepositoryInterface;
use Maatify\I18n\Exception\TranslationKeyAlreadyExistsException;
use Maatify\I18n\Exception\TranslationKeyCreateFailedException;
use Maatify\I18n\Exception\TranslationKeyNotFoundException;
use Maatify\I18n\Exception\TranslationUpdateFailedException;
use Maatify\I18n\Exception\TranslationUpsertFailedException;
use Maatify\LanguageCore\Contract\LanguageRepositoryInterface;
use Maatify\LanguageCore\Exception\LanguageNotFoundException;

final readonly class TranslationWriteService
{
    public function __construct(
        private I18nTransactionManagerInterface $tx,
        private LanguageRepositoryInterface $languageRepository,
        private TranslationKeyRepositoryInterface $keyRepository,
        private TranslationRepositoryInterface $translationRepository,
        private I18nGovernancePolicyService $governancePolicy,
        private MissingCounterService $missingCounter
    ) {
    }

    public function createKey(
        string $scope,
        string $domain,
        string $key,
        ?string $description = null
    ): int {
        return $this->tx->run(function () use ($scope, $domain, $key, $description): int {

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

            // Must stay inside same TX
            $this->missingCounter->onKeyCreated($id);

            return $id;
        });
    }

    public function renameKey(
        int $keyId,
        string $scope,
        string $domain,
        string $key
    ): void {
        $this->tx->run(function () use ($keyId, $scope, $domain, $key): void {

            $existingKey = $this->keyRepository->getById($keyId);

            if ($existingKey === null) {
                throw new TranslationKeyNotFoundException($keyId);
            }

            $this->governancePolicy
                ->assertScopeAndDomainAllowed($scope, $domain);

            $duplicate = $this->keyRepository
                ->getByStructuredKey($scope, $domain, $key);

            if ($duplicate !== null && $duplicate->id !== $keyId) {
                throw new TranslationKeyAlreadyExistsException(
                    $scope,
                    $domain,
                    $key
                );
            }

            $oldScope  = $existingKey->scope;
            $oldDomain = $existingKey->domain;

            $changedScope  = $oldScope !== $scope;
            $changedDomain = $oldDomain !== $domain;

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

            /*
             * 🔥 IMPORTANT:
             * If scope/domain changed → adjust derived summary layer.
             *
             * We must:
             * - decrement total_keys from old scope/domain
             * - increment total_keys to new scope/domain
             *
             * This must stay inside same TX.
             */
            if ($changedScope || $changedDomain) {

                $this->missingCounter->onKeyMoved(
                    $oldScope,
                    $oldDomain,
                    $scope,
                    $domain
                );
            }
        });
    }

    public function updateKeyDescription(
        int $keyId,
        string $description
    ): void {
        $this->tx->run(function () use ($keyId, $description): void {

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
        });
    }

    public function upsertTranslation(
        int $languageId,
        int $keyId,
        string $value
    ): int {
        return $this->tx->run(function () use ($languageId, $keyId, $value): int {

            if ($this->languageRepository->getById($languageId) === null) {
                throw new LanguageNotFoundException($languageId);
            }

            if ($this->keyRepository->getById($keyId) === null) {
                throw new TranslationKeyNotFoundException($keyId);
            }

            $result = $this->translationRepository->upsert(
                $languageId,
                $keyId,
                $value
            );

            if ($result->id <= 0) {
                throw new TranslationUpsertFailedException(
                    $languageId,
                    $keyId
                );
            }

            if ($result->created) {
                // Must be inside same TX
                $this->missingCounter->onTranslationCreated(
                    $languageId,
                    $keyId
                );
            }

            return $result->id;
        });
    }

    public function deleteTranslation(
        int $languageId,
        int $keyId
    ): void {
        $this->tx->run(function () use ($languageId, $keyId): void {

            if ($this->languageRepository->getById($languageId) === null) {
                throw new LanguageNotFoundException($languageId);
            }

            if ($this->keyRepository->getById($keyId) === null) {
                throw new TranslationKeyNotFoundException($keyId);
            }

            $affected = $this->translationRepository
                ->deleteByLanguageAndKey($languageId, $keyId);

            if ($affected > 0) {
                // Must be inside same TX
                $this->missingCounter->onTranslationDeleted(
                    $languageId,
                    $keyId
                );
            }
        });
    }
}

