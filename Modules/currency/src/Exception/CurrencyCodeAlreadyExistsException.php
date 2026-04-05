<?php

declare(strict_types=1);

namespace Maatify\Currency\Exception;

use Maatify\Exceptions\Exception\Conflict\GenericConflictMaatifyException;

/**
 * Thrown when attempting to create or update a currency with a code that is
 * already used by another row (UNIQUE KEY violation on `currencies.code`).
 *
 * Family  : Conflict
 * HTTP    : 409
 * Category: CONFLICT
 */
final class CurrencyCodeAlreadyExistsException extends GenericConflictMaatifyException
    implements CurrencyExceptionInterface
{
    public static function withCode(string $code): self
    {
        return new self(sprintf('A currency with code "%s" already exists.', $code));
    }
}
