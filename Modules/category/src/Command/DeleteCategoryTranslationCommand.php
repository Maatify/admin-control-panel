<?php

declare(strict_types=1);

namespace Maatify\Category\Command;

/**
 * Removes the localised name for a specific (category, language) pair.
 * After deletion queries return the base name transparently.
 */
final class DeleteCategoryTranslationCommand
{
    public function __construct(
        public readonly int $categoryId,
        public readonly int $languageId,
    ) {}
}

