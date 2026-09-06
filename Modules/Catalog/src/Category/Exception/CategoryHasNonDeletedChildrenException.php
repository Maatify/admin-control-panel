<?php

declare(strict_types=1);

namespace Maatify\Catalog\Category\Exception;

use Maatify\Catalog\Exception\CatalogExceptionInterface;
use Maatify\Exceptions\Exception\BusinessRule\BusinessRuleMaatifyException;

final class CategoryHasNonDeletedChildrenException extends BusinessRuleMaatifyException
    implements CatalogExceptionInterface
{
    public static function withId(int $categoryId): self
    {
        return new self(sprintf(
            'Category %d cannot be soft-deleted while it has non-deleted children.',
            $categoryId,
        ));
    }
}
