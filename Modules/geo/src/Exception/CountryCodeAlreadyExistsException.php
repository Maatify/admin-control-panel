<?php

declare(strict_types=1);

namespace Maatify\Geo\Exception;

use Maatify\Exceptions\Exception\Conflict\GenericConflictMaatifyException;

/**
 * Thrown when a country code (ISO alpha-2) is already taken.
 *
 * Family  : Conflict
 * HTTP    : 409
 * Category: CONFLICT
 */
final class CountryCodeAlreadyExistsException extends GenericConflictMaatifyException
    implements GeoExceptionInterface
{
    public static function withCode(string $code): self
    {
        return new self(sprintf('Country with code "%s" already exists.', strtoupper($code)));
    }
}

