<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-24 22:44
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Support;

use Maatify\AdminKernel\Domain\LanguageCore\DTO\LanguageWithSettingsItemDTO;

final class LanguageCollectionHelper
{
    /**
     * @param list<LanguageWithSettingsItemDTO> $languages
     */
    public static function findById(
        array $languages,
        int $languageId
    ): ?LanguageWithSettingsItemDTO {
        foreach ($languages as $lang) {
            if ($lang->id === $languageId) {
                return $lang;
            }
        }

        return null;
    }
}
