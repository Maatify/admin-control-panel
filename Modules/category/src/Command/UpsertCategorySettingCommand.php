<?php

declare(strict_types=1);

namespace Maatify\Category\Command;

/**
 * Creates or updates a key-value setting for a category.
 * INSERT … ON DUPLICATE KEY UPDATE semantics (upsert).
 * Safe to call whether the setting exists or not.
 */
final class UpsertCategorySettingCommand
{
    public function __construct(
        public readonly int    $categoryId,
        public readonly string $key,
        public readonly string $value,
    ) {}
}

