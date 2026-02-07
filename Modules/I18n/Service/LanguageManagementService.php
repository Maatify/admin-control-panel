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
use Maatify\I18n\Contract\LanguageSettingsRepositoryInterface;
use Maatify\I18n\Enum\TextDirectionEnum;
use Maatify\I18n\Exception\LanguageAlreadyExistsException;
use Maatify\I18n\Exception\LanguageCreateFailedException;
use Maatify\I18n\Exception\LanguageInvalidFallbackException;
use Maatify\I18n\Exception\LanguageNotFoundException;
use Maatify\I18n\Exception\LanguageUpdateFailedException;

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
