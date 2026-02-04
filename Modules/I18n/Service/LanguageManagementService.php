<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-04 01:34
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\I18n\Service;

use Maatify\I18n\Contract\LanguageRepositoryInterface;
use Maatify\I18n\Contract\LanguageSettingsRepositoryInterface;
use Maatify\I18n\Enum\TextDirectionEnum;
use RuntimeException;

final readonly class LanguageManagementService
{
    public function __construct(
        private LanguageRepositoryInterface $languageRepository,
        private LanguageSettingsRepositoryInterface $settingsRepository
    )
    {
    }

    public function createLanguage(
        string $name,
        string $code,
        TextDirectionEnum $direction,
        ?string $icon,
        int $sortOrder,
        bool $isActive = true,
        ?int $fallbackLanguageId = null
    ): int
    {
        if ($this->languageRepository->getByCode($code) !== null) {
            throw new RuntimeException('Language code already exists.');
        }

        if ($fallbackLanguageId !== null) {
            if ($this->languageRepository->getById($fallbackLanguageId) === null) {
                throw new RuntimeException('Fallback language does not exist.');
            }
        }

        $languageId = $this->languageRepository->create(
            $name,
            $code,
            $isActive,
            $fallbackLanguageId
        );

        if ($languageId <= 0) {
            throw new RuntimeException('Failed to create language.');
        }

        $this->settingsRepository->upsert(
            $languageId,
            $direction,
            $icon,
            $sortOrder
        );

        return $languageId;
    }

    public function setLanguageActive(int $languageId, bool $isActive): void
    {
        if ($this->languageRepository->getById($languageId) === null) {
            throw new RuntimeException('Language not found.');
        }

        $this->languageRepository->setActive($languageId, $isActive);
    }

    public function updateLanguageSettings(
        int $languageId,
        TextDirectionEnum $direction,
        ?string $icon,
        int $sortOrder
    ): void
    {
        if ($this->languageRepository->getById($languageId) === null) {
            throw new RuntimeException('Language not found.');
        }

        $this->settingsRepository->upsert(
            $languageId,
            $direction,
            $icon,
            $sortOrder
        );
    }

    public function setFallbackLanguage(
        int $languageId,
        int $fallbackLanguageId
    ): void {
        if ($languageId === $fallbackLanguageId) {
            throw new RuntimeException('Language cannot fallback to itself.');
        }

        if ($this->languageRepository->getById($languageId) === null) {
            throw new RuntimeException('Language not found.');
        }

        if ($this->languageRepository->getById($fallbackLanguageId) === null) {
            throw new RuntimeException('Fallback language not found.');
        }

        $this->languageRepository->setFallbackLanguage(
            $languageId,
            $fallbackLanguageId
        );
    }
}
