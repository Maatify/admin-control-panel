<?php

declare(strict_types=1);

namespace Maatify\Storage\DTO;

use DateTimeImmutable;

/**
 * File metadata returned by storage adapters.
 *
 * Contains basic information about a stored file for auditing and analytics purposes.
 */
final readonly class FileMetadata
{
    public function __construct(
        /**
         * File size in bytes.
         */
        public int $sizeBytes,

        /**
         * Last modification timestamp (null if not available).
         */
        public ?DateTimeImmutable $lastModified = null,

        /**
         * Entity tag (e.g., S3 ETag) for change detection.
         * Null if not available.
         */
        public ?string $eTag = null,

        /**
         * MIME type (e.g., 'image/jpeg', 'video/mp4').
         * Null if not available or not detected.
         */
        public ?string $contentType = null,
    ) {}

    /**
     * Convenience alias for contentType property.
     *
     * Returns the MIME type as a string, null if not available.
     */
    public function mimeType(): ?string
    {
        return $this->contentType;
    }
}
