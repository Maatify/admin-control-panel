<?php

declare(strict_types=1);

namespace Maatify\Catalog\Category\Contract;

use Maatify\Catalog\Category\DTO\CategoryDTO;
use Maatify\Catalog\Category\DTO\CategoryTranslationDTO;

/** Read port consumed by Category business orchestration. */
interface CategoryQueryReaderInterface
{
    public function findById(int $categoryId): ?CategoryDTO;

    /** Finds both active and soft-deleted rows so stable codes cannot be reused. */
    public function findByCode(string $code): ?CategoryDTO;

    /** Returns true only when a non-deleted child exists. */
    public function hasNonDeletedChildren(int $categoryId): bool;

    public function findTranslationById(int $translationId): ?CategoryTranslationDTO;
}
