<?php

declare(strict_types=1);

namespace Maatify\Currency\Exception;

use Maatify\Exceptions\Exception\NotFound\ResourceNotFoundMaatifyException;

/**
 * Thrown when no translation row exists for a given (currency_id, language_id) pair.
 *
 * Family  : NotFound
 * HTTP    : 404
 * Category: NOT_FOUND
 */
final class CurrencyTranslationNotFoundException extends ResourceNotFoundMaatifyException
    implements CurrencyExceptionInterface
{
    public static function for(int $currencyId, int $languageId): self
    {
        return new self(sprintf(
            'No translation found for currency id %d in language id %d.',
            $currencyId,
            $languageId,
        ));
    }
}
