<?php

declare(strict_types=1);

namespace Maatify\Catalog\Category\DTO;

use Maatify\Catalog\Category\Enum\CategoryStatusEnum;

/** Validated input for changing only a Category status. */
final readonly class UpdateCategoryStatusDTO
{
    public int $categoryId;
    public CategoryStatusEnum $status;

    public function __construct(int|string $categoryId, CategoryStatusEnum $status)
    {
        $this->categoryId = (new CategoryIdDTO($categoryId, 'categoryId'))->value;
        $this->status = $status;
    }
}
