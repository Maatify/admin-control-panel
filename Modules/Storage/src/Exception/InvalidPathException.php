<?php

declare(strict_types=1);

namespace Maatify\Storage\Exception;

final class InvalidPathException extends ReaderException
{
    public static function emptyPath(): self
    {
        return new self('Storage path cannot be empty');
    }

    public static function containsTraversal(string $path): self
    {
        return new self(sprintf('Path contains traversal attempt: %s', $path));
    }

    public static function invalidPath(string $path): self
    {
        return new self(sprintf('Invalid storage path: %s', $path));
    }

    public static function containsNullBytes(string $path): self
    {
        return new self(sprintf('Storage path cannot contain null bytes: %s', $path));
    }
}
