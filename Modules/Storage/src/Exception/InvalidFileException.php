<?php

declare(strict_types=1);

namespace Maatify\Storage\Exception;

/**
 * Thrown when a file fails validation.
 *
 * Per Maatify Module Building Standard section 6 - named constructors required.
 */
final class InvalidFileException extends StorageException
{
    // ── Named Constructors ──────────────────────────────────────────

    public static function missingFilename(): self
    {
        return new self('Invalid file name provided by client.');
    }

    /**
     * @param array<string> $allowed
     */
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

    /**
     * @param array<string> $allowed
     */
    public static function unsupportedMimeType(string $mimeType, array $allowed): self
    {
        return new self(
            sprintf(
                'Unsupported MIME type "%s". Allowed: %s.',
                $mimeType,
                implode(', ', $allowed),
            )
        );
    }

    public static function fileTooLarge(int $given, int $maxSize): self
    {
        return new self(sprintf(
            'File size [%s] exceeds maximum allowed size [%s].',
            self::formatBytes($given),
            self::formatBytes($maxSize)
        ));
    }

    public static function imageTooSmall(int $width, int $height, \Maatify\Storage\Config\ImageDimensions $dims): self
    {
        return new self(sprintf(
            'Image dimensions [%dx%d] below minimum [%dx%d].',
            $width,
            $height,
            $dims->minWidth,
            $dims->minHeight
        ));
    }

    public static function imageTooLarge(int $width, int $height, \Maatify\Storage\Config\ImageDimensions $dims): self
    {
        return new self(sprintf(
            'Image dimensions [%dx%d] exceed maximum [%dx%d].',
            $width,
            $height,
            $dims->maxWidth,
            $dims->maxHeight
        ));
    }

    public static function invalidImage(): self
    {
        return new self('File is not a valid image.');
    }

    // ── Helper ──────────────────────────────────────────────────────

    private static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
