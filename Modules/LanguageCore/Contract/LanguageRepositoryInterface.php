<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/language-core
 * @Project     maatify:language-core
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-04 01:14
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/language-core view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\LanguageCore\Contract;

use Maatify\LanguageCore\DTO\LanguageCollectionDTO;
use Maatify\LanguageCore\DTO\LanguageDTO;

interface LanguageRepositoryInterface
{
    public function create(string $name, string $code, bool $isActive, ?int $fallbackLanguageId): int;

    public function getById(int $id): ?LanguageDTO;

    /**
     * Same as getById() but takes a row lock (`SELECT ... FOR UPDATE`).
     * Must be called inside a transaction; used to serialize identity
     * (code) transitions so they never act on a stale code.
     */
    public function getByIdForUpdate(int $id): ?LanguageDTO;

    public function getByCode(string $code): ?LanguageDTO;

    public function listAll(): LanguageCollectionDTO;

    public function setActive(int $id, bool $isActive): bool;

    public function setFallbackLanguage(int $id, ?int $fallbackLanguageId): bool;

    public function clearFallbackLanguage(
        int $languageId
    ): bool;

    public function updateName(int $id, string $name): bool;

    public function updateCode(int $id, string $code): bool;

    /**
     * Returns languages usable as UI context selectors.
     * Active only. Ordered by sort_order.
     */
    public function listActiveForSelect(): LanguageCollectionDTO;
}
