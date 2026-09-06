<?php

declare(strict_types=1);

namespace Maatify\Catalog\Category\DTO;

/** Validated input for restoring a Category with its existing identity. */
final readonly class RestoreCategoryDTO
{
    public int $categoryId;

    public function __construct(int|string $categoryId)
    {
        $this->categoryId = (new CategoryIdDTO($categoryId, 'categoryId'))->value;
    }
}
