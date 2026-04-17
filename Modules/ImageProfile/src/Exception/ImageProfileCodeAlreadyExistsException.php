<?php

declare(strict_types=1);

namespace Maatify\ImageProfile\Exception;

use Maatify\Exceptions\Exception\Conflict\GenericConflictMaatifyException;

final class ImageProfileCodeAlreadyExistsException extends GenericConflictMaatifyException
    implements ImageProfileExceptionInterface
{
    public static function withCode(string $code): self
    {
        return new self(sprintf('An image profile with code "%s" already exists.', $code));
    }
}
