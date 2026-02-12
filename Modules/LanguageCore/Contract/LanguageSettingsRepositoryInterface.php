<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/language-core
 * @Project     maatify:language-core
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-04 01:15
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/language-core view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\LanguageCore\Contract;

use Maatify\LanguageCore\DTO\LanguageSettingsDTO;
use Maatify\LanguageCore\Enum\TextDirectionEnum;

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
    ): bool;

    public function updateDirection(int $languageId, TextDirectionEnum $direction): bool;

    public function updateIcon(int $languageId, ?string $icon): bool;

    public function updateSortOrder(int $languageId, int $sortOrder): bool;

    public function repositionSortOrder(
        int $languageId,
        int $currentSort,
        int $targetSort
    ): void;

    public function getNextSortOrder(): int;

}
