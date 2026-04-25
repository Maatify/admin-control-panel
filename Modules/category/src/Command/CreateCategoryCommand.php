<?php

declare(strict_types=1);

namespace Maatify\Category\Command;

/**
 * Carries all data required to persist a new category.
 *
 * parentId = null  → creating a ROOT category (depth 0)
 * parentId = int   → creating a SUB-CATEGORY of that parent (depth 1)
 *
 * displayOrder = 0 → "append to end of this level" signal.
 * The repository resolves this atomically via INSERT … SELECT MAX,
 * scoped by parent_id, so no race condition occurs.
 */
final class CreateCategoryCommand
{
    public function __construct(
        public readonly string  $name,
        public readonly string  $slug,
        public readonly ?string $description  = null,
        public readonly ?int    $parentId     = null,
        public readonly bool    $isActive     = true,
        public readonly int     $displayOrder = 0,
        public readonly ?string $notes        = null,
    ) {}
}

