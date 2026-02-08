<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-07 18:27
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\Scope\Writer;

interface I18nScopeUpdaterInterface
{
    public function existsById(int $id): bool;

    public function existsByCode(string $code): bool;

    public function isCodeInUse(string $code): bool;

    public function getCurrentCode(int $id): string;

    public function changeCode(int $id, string $newCode): void;

    public function setActive(int $id, int $isActive): void;

    public function repositionSortOrder(int $id, int $newPosition): void;

    public function updateMetadata(
        int $id,
        ?string $name,
        ?string $description
    ): void;
}
