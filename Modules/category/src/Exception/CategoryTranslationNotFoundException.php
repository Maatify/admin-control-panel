<?php

declare(strict_types=1);

namespace Maatify\Category\Exception;

use Maatify\Exceptions\Exception\NotFound\ResourceNotFoundMaatifyException;

/**
 * Thrown when a category_translations row cannot be located.
 *
 * Family  : NotFound
 * HTTP    : 404
 * Category: NOT_FOUND
 */
final class CategoryTranslationNotFoundException extends ResourceNotFoundMaatifyException
    implements CategoryExceptionInterface
{
    public static function for(int $categoryId, int $languageId): self
    {
        return new self(
            sprintf(
                'Translation not found for category_id=%d, language_id=%d.',
                $categoryId,
                $languageId,
            ),
        );
    }
}

