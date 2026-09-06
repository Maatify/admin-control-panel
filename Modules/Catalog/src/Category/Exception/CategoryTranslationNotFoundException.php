<?php

declare(strict_types=1);

namespace Maatify\Catalog\Category\Exception;

use Maatify\Catalog\Exception\CatalogExceptionInterface;
use Maatify\Exceptions\Exception\NotFound\ResourceNotFoundMaatifyException;

final class CategoryTranslationNotFoundException extends ResourceNotFoundMaatifyException
    implements CatalogExceptionInterface
{
    public static function withId(int $translationId): self
    {
        return new self(sprintf('Category Translation with id %d was not found.', $translationId));
    }
}
