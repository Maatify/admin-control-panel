<?php

declare(strict_types=1);

namespace Maatify\Category\Command;

use Maatify\Category\Enum\CategoryImageTypeEnum;

/**
 * Creates or replaces the image path for one (category, type, language) slot.
 * Uses INSERT … ON DUPLICATE KEY UPDATE semantics — safe to call either way.
 */
final class UpsertCategoryImageCommand
{
    public function __construct(
        public readonly int                   $categoryId,
        public readonly CategoryImageTypeEnum $imageType,
        public readonly int                   $languageId,
        public readonly string                $path,
    ) {}
}

