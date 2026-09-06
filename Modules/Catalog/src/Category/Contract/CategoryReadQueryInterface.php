<?php

declare(strict_types=1);

namespace Maatify\Catalog\Category\Contract;

use Maatify\Catalog\Category\DTO\CategoryCollectionDTO;
use Maatify\Catalog\Category\DTO\CategoryDTO;
use Maatify\Catalog\Category\DTO\CategoryTranslationCollectionDTO;

/** Dedicated public read port for visible Category query behavior. */
interface CategoryReadQueryInterface
{
    /** Finds a non-deleted, active Category whose complete ancestor chain is visible. */
    public function findVisibleById(int $categoryId): ?CategoryDTO;

    /** Lists visible root Categories in deterministic display order. */
    public function listVisibleRootCategories(): CategoryCollectionDTO;

    /**
     * Lists visible direct children of an active parent whose complete ancestor
     * chain is visible.
     */
    public function listVisibleChildren(int $parentId): CategoryCollectionDTO;

    /**
     * Lists non-deleted translations for a visible Category in language-code
     * order. Language validation and fallback remain Host responsibilities.
     */
    public function listVisibleTranslations(int $categoryId): CategoryTranslationCollectionDTO;
}
