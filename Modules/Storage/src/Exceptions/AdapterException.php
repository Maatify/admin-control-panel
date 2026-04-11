<?php

declare(strict_types=1);

namespace Maatify\Storage\Exceptions;

final class AdapterException extends StorageException
{
    public static function failedToCreateDirectory(string $directory): self
    {
        return new self('Failed to create directory: ' . $directory);
    }

    public static function failedToDeleteFile(string $path): self
    {
        return new self('Failed to delete file: ' . $path);
    }
}
