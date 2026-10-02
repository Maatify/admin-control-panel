<?php

declare(strict_types=1);

namespace Maatify\Storage\Exception;

/**
 * Thrown when the storage adapter encounters an error.
 *
 * Per Maatify Module Building Standard section 6 - named constructors required.
 */
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

    public static function failedToOpenFile(string $path): self
    {
        return new self('Failed to open file for reading: ' . $path);
    }

    public static function failedToReadLocalFile(string $path): self
    {
        return new self('Failed to open local file for storage transfer: ' . $path);
    }

    public static function failedToCopyFile(string $source, string $destination): self
    {
        return new self("Failed to copy file from [{$source}] to [{$destination}].");
    }
}
