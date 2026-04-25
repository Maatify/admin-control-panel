<?php

declare(strict_types=1);

namespace Maatify\Category\Exception;

use Maatify\Exceptions\Exception\NotFound\ResourceNotFoundMaatifyException;

/**
 * Thrown when a category_settings row cannot be found for a given (category_id, key) pair.
 *
 * Family  : NotFound
 * HTTP    : 404
 * Category: NOT_FOUND
 */
final class CategorySettingNotFoundException extends ResourceNotFoundMaatifyException
    implements CategoryExceptionInterface
{
    public static function for(int $categoryId, string $key): self
    {
        return new self(sprintf(
            'No setting found for category id %d with key "%s".',
            $categoryId,
            $key,
        ));
    }
}

