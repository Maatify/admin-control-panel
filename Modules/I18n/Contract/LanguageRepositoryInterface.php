<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/i18n
 * @Project     maatify:i18n
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-04 01:14
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/i18n view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\I18n\Contract;

use Maatify\I18n\DTO\LanguageCollectionDTO;
use Maatify\I18n\DTO\LanguageDTO;

interface LanguageRepositoryInterface
{
    public function create(string $name, string $code, bool $isActive, ?int $fallbackLanguageId): int;

    public function getById(int $id): ?LanguageDTO;

    public function getByCode(string $code): ?LanguageDTO;

    public function listAll(): LanguageCollectionDTO;

    public function setActive(int $id, bool $isActive): void;

    public function setFallbackLanguage(int $id, ?int $fallbackLanguageId): void;

    public function clearFallbackLanguage(
        int $languageId
    ): void;

    public function updateName(int $id, string $name): void;

    public function updateCode(int $id, string $code): void;

    public function getNextSortOrder(): int;

    /**
     * Returns languages usable as UI context selectors.
     * Active only. Ordered by sort_order.
     */
    public function listActiveForSelect(): LanguageCollectionDTO;
}
