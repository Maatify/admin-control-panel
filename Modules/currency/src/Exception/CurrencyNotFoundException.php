<?php

declare(strict_types=1);

namespace Maatify\Currency\Exception;

use Maatify\Exceptions\Exception\NotFound\ResourceNotFoundMaatifyException;

/**
 * Thrown when a currency row cannot be located by id or code.
 *
 * Family  : NotFound
 * HTTP    : 404
 * Category: NOT_FOUND
 */
final class CurrencyNotFoundException extends ResourceNotFoundMaatifyException
    implements CurrencyExceptionInterface
{
    public static function withId(int $id): self
    {
        return new self(sprintf('Currency with id %d not found.', $id));
    }

    public static function withCode(string $code): self
    {
        return new self(sprintf('Currency with code "%s" not found.', $code));
    }
}
