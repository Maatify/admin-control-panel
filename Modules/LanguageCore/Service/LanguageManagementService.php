<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/language-core
 * @Project     maatify:language-core
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-04 01:34
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/language-core view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\LanguageCore\Service;

use Maatify\LanguageCore\Contract\LanguageRepositoryInterface;
use Maatify\LanguageCore\Contract\LanguageSettingsRepositoryInterface;
use Maatify\LanguageCore\Enum\TextDirectionEnum;
use Maatify\LanguageCore\Exception\LanguageAlreadyExistsException;
use Maatify\LanguageCore\Exception\LanguageCreateFailedException;
use Maatify\LanguageCore\Exception\LanguageInvalidFallbackException;
use Maatify\LanguageCore\Exception\LanguageNotFoundException;
use Maatify\LanguageCore\Exception\LanguageUpdateFailedException;

final readonly class LanguageManagementService
{
    public function __construct(
        private LanguageRepositoryInterface $languageRepository,
        private LanguageSettingsRepositoryInterface $settingsRepository
    ) {
    }

    public function createLanguage(
        string $name,
        string $code,
        TextDirectionEnum $direction,
        ?string $icon,
        bool $isActive = true,
        ?int $fallbackLanguageId = null
    ): int {
        if ($this->languageRepository->getByCode($code) !== null) {
            throw new LanguageAlreadyExistsException($code);
        }

        if ($fallbackLanguageId !== null) {
            if ($this->languageRepository->getById($fallbackLanguageId) === null) {
                throw new LanguageNotFoundException($fallbackLanguageId);
            }
        }

        $sortOrder = $this->settingsRepository->getNextSortOrder();

        $languageId = $this->languageRepository->create(
            $name,
            $code,
            $isActive,
            $fallbackLanguageId
        );

        if ($languageId <= 0) {
            throw new LanguageCreateFailedException();
        }

        if (
            !$this->settingsRepository->upsert(
                $languageId,
                $direction,
                $icon
            )
        ) {
            throw new LanguageUpdateFailedException('settings');
        }

        // intentionally fail-soft
        $this->settingsRepository->updateSortOrder(
            $languageId,
            $sortOrder
        );

        return $languageId;
    }

    public function setLanguageActive(int $languageId, bool $isActive): void
    {
        if ($this->languageRepository->getById($languageId) === null) {
            throw new LanguageNotFoundException($languageId);
        }

        $ok = $this->languageRepository->setActive($languageId, $isActive);

        if (!$ok) {
            throw new LanguageUpdateFailedException('is_active');
        }
    }

    public function updateLanguageSettings(
        int $languageId,
        TextDirectionEnum $direction,
        ?string $icon,
    ): void {
        if ($this->languageRepository->getById($languageId) === null) {
            throw new LanguageNotFoundException($languageId);
        }

        if (
            !$this->settingsRepository->upsert(
                $languageId,
                $direction,
                $icon
            )
        ) {
            throw new LanguageUpdateFailedException('settings');
        }
    }

    public function setFallbackLanguage(
        int $languageId,
        int $fallbackLanguageId
    ): void {
        if ($languageId === $fallbackLanguageId) {
            throw new LanguageInvalidFallbackException($languageId);
        }

        if ($this->languageRepository->getById($languageId) === null) {
            throw new LanguageNotFoundException($languageId);
        }

        if ($this->languageRepository->getById($fallbackLanguageId) === null) {
            throw new LanguageNotFoundException($fallbackLanguageId);
        }

        if (
            !$this->languageRepository->setFallbackLanguage(
                $languageId,
                $fallbackLanguageId
            )
        ) {
            throw new LanguageInvalidFallbackException($languageId);
        }
    }

    public function clearFallbackLanguage(int $languageId): void
    {
        if ($this->languageRepository->getById($languageId) === null) {
            throw new LanguageNotFoundException($languageId);
        }

        if(!$this->languageRepository->clearFallbackLanguage($languageId)) {
            throw new LanguageInvalidFallbackException($languageId);
        }
    }

    public function updateLanguageSortOrder(
        int $languageId,
        int $newSortOrder
    ): void {
        $settings = $this->settingsRepository->getByLanguageId($languageId);

        if ($settings === null) {
            throw new LanguageNotFoundException($languageId);
        }

        $currentSort = $settings->sortOrder;

        if ($newSortOrder === $currentSort) {
            return;
        }

        if ($newSortOrder < 1) {
            $newSortOrder = 1;
        }

        $this->settingsRepository->repositionSortOrder(
            languageId: $languageId,
            currentSort: $currentSort,
            targetSort: $newSortOrder
        );
    }

    public function updateLanguageName(
        int $languageId,
        string $name
    ): void {
        if ($this->languageRepository->getById($languageId) === null) {
            throw new LanguageNotFoundException($languageId);
        }

        if (trim($name) === '') {
            throw new LanguageUpdateFailedException('name');
        }

        if (!$this->languageRepository->updateName($languageId, $name)) {
            throw new LanguageUpdateFailedException('name');
        }
    }

    public function updateLanguageCode(
        int $languageId,
        string $code
    ): void {
        if ($this->languageRepository->getById($languageId) === null) {
            throw new LanguageNotFoundException($languageId);
        }

        $code = trim($code);

        if ($code === '') {
            throw new LanguageUpdateFailedException('code');
        }

        $existing = $this->languageRepository->getByCode($code);

        if ($existing !== null && $existing->id !== $languageId) {
            throw new LanguageAlreadyExistsException($code);
        }

        if (
            !$this->languageRepository->updateCode(
                $languageId,
                $code
            )
        ) {
            throw new LanguageUpdateFailedException('code');
        }
    }
}
