<?php

declare(strict_types=1);

namespace Maatify\Catalog\Category\Contract;

use Maatify\Catalog\Category\DTO\CategoryCollectionDTO;
use Maatify\Catalog\Category\DTO\CategoryDTO;
use Maatify\Catalog\Category\DTO\CategoryTranslationCollectionDTO;

/** Public application contract for visible Category reads and lists. */
interface CategoryQueryServiceInterface
{
    /**
     * @throws \Maatify\Catalog\Category\Exception\CategoryNotFoundException
     */
    public function getById(int $categoryId): CategoryDTO;

    public function listRootCategories(): CategoryCollectionDTO;

    public function listChildren(int $parentId): CategoryCollectionDTO;

    public function listTranslations(int $categoryId): CategoryTranslationCollectionDTO;
}
