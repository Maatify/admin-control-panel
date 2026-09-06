<?php

declare(strict_types=1);

namespace Maatify\Catalog\Category\DTO;

use Maatify\Catalog\Category\Exception\CategoryInvalidArgumentException;

/** Validated input for the dedicated Category display-order operation. */
final readonly class UpdateCategoryDisplayOrderDTO
{
    public int $categoryId;
    public int $displayOrder;

    public function __construct(int|string $categoryId, int $displayOrder)
    {
        if ($displayOrder < 1) {
            throw CategoryInvalidArgumentException::invalidDisplayOrder($displayOrder);
        }

        $this->categoryId = (new CategoryIdDTO($categoryId, 'categoryId'))->value;
        $this->displayOrder = $displayOrder;
    }
}
