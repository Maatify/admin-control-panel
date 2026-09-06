<?php

declare(strict_types=1);

namespace Maatify\Catalog\Category\Exception;

use Maatify\Catalog\Exception\CatalogExceptionInterface;
use Maatify\Exceptions\Exception\BusinessRule\BusinessRuleMaatifyException;

final class CategoryCycleException extends BusinessRuleMaatifyException
    implements CatalogExceptionInterface
{
    public static function forMove(int $categoryId, int $newParentId): self
    {
        return new self(sprintf(
            'Moving Category %d under Category %d would create a cycle.',
            $categoryId,
            $newParentId,
        ));
    }
}
