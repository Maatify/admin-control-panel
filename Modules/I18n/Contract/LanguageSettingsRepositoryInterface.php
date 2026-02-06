<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/i18n
 * @Project     maatify:i18n
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-04 01:15
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/i18n view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\I18n\Contract;

use Maatify\I18n\DTO\LanguageSettingsDTO;
use Maatify\I18n\Enum\TextDirectionEnum;

interface LanguageSettingsRepositoryInterface
{
    public function getByLanguageId(int $languageId): ?LanguageSettingsDTO;

    /**
     * Upsert settings row for a language.
     */
    public function upsert(
        int $languageId,
        TextDirectionEnum $direction,
        ?string $icon,
    ): void;

    public function updateDirection(int $languageId, TextDirectionEnum $direction): void;

    public function updateIcon(int $languageId, ?string $icon): void;

    public function updateSortOrder(int $languageId, int $sortOrder): void;

    public function repositionSortOrder(
        int $languageId,
        int $currentSort,
        int $targetSort
    ): void;
}
