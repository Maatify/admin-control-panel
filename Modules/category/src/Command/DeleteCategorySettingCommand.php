<?php

declare(strict_types=1);

namespace Maatify\Category\Command;

/**
 * Removes one specific setting from a category by key.
 * Silent no-op if the setting does not exist.
 */
final class DeleteCategorySettingCommand
{
    public function __construct(
        public readonly int    $categoryId,
        public readonly string $key,
    ) {}
}

