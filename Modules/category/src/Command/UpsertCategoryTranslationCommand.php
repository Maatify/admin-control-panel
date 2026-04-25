<?php

declare(strict_types=1);

namespace Maatify\Category\Command;

/**
 * Creates or updates the localised name for a (category, language) pair.
 * INSERT … ON DUPLICATE KEY UPDATE semantics (upsert).
 */
final class UpsertCategoryTranslationCommand
{
    public function __construct(
        public readonly int     $categoryId,
        public readonly int     $languageId,
        public readonly string  $translatedName,
        public readonly ?string $translatedDescription = null,
    ) {}
}

