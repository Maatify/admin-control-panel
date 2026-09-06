<?php

declare(strict_types=1);

namespace Maatify\Catalog\Category\DTO;

/** Validated input for soft-deleting a Category. */
final readonly class SoftDeleteCategoryDTO
{
    public int $categoryId;

    public function __construct(int|string $categoryId)
    {
        $this->categoryId = (new CategoryIdDTO($categoryId, 'categoryId'))->value;
    }
}
