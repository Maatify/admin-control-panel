<?php

declare(strict_types=1);

namespace Maatify\Category\Command;

/**
 * Toggles the active / inactive flag for a category.
 */
final class UpdateCategoryStatusCommand
{
    public function __construct(
        public readonly int  $id,
        public readonly bool $isActive,
    ) {}
}

