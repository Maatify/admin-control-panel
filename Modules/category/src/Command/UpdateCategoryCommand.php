<?php

declare(strict_types=1);

namespace Maatify\Category\Command;

/**
 * Carries all data required to update an existing category (full replace).
 *
 * Changing parentId is allowed — the Service validates:
 *   - The new parent exists
 *   - The new parent is a root (no circular reference or depth violation)
 *
 * Setting parentId to null promotes a sub-category to a root category.
 *
 * If displayOrder differs from the stored value the repository will
 * automatically re-sort surrounding rows within the same parent level.
 */
final class UpdateCategoryCommand
{
    public function __construct(
        public readonly int     $id,
        public readonly string  $name,
        public readonly string  $slug,
        public readonly ?int    $parentId,
        public readonly bool    $isActive,
        public readonly int     $displayOrder,
        public readonly ?string $description  = null,
        public readonly ?string $notes        = null,
    ) {}
}

