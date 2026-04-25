<?php

declare(strict_types=1);

namespace Maatify\Category\Command;

use Maatify\Category\Enum\CategoryImageTypeEnum;

/**
 * Removes one (category, type, language) image slot.
 * Silent no-op if the slot does not exist.
 */
final class DeleteCategoryImageCommand
{
    public function __construct(
        public readonly int                   $categoryId,
        public readonly CategoryImageTypeEnum $imageType,
        public readonly int                   $languageId,
    ) {}
}

