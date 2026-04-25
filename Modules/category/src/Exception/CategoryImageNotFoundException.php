<?php

declare(strict_types=1);

namespace Maatify\Category\Exception;

use Maatify\Exceptions\Exception\NotFound\ResourceNotFoundMaatifyException;

/**
 * Thrown when a category_images row cannot be located.
 *
 * Family  : NotFound
 * HTTP    : 404
 * Category: NOT_FOUND
 */
final class CategoryImageNotFoundException extends ResourceNotFoundMaatifyException
    implements CategoryExceptionInterface
{
    public static function for(int $categoryId, string $imageType, int $languageId): self
    {
        return new self(sprintf(
            'No image found for category id %d, type "%s", language id %d.',
            $categoryId,
            $imageType,
            $languageId,
        ));
    }
}

