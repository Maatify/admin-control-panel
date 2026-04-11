<?php

declare(strict_types=1);

namespace Maatify\Storage\Exceptions;

final class InvalidFileException extends StorageException
{
    public static function missingFilename(): self
    {
        return new self('Invalid file name provided by client.');
    }

    /** @param string[] $allowed */
    public static function unsupportedExtension(string $extension, array $allowed): self
    {
        return new self(
            sprintf(
                'Invalid file extension "%s". Allowed: %s.',
                $extension,
                implode(', ', $allowed),
            )
        );
    }
}
