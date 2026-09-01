<?php

declare(strict_types=1);

namespace Maatify\Storage\Services;

use Maatify\Storage\Config\ImageDimensions;
use Maatify\Storage\DTO\StoredFile;
use Psr\Http\Message\UploadedFileInterface;

/**
 * ImageUploadService wrapper for public uploads (CDN accessible).
 *
 * This service is configured via DI to upload images to a public bucket with
 * public-read ACL and CDN URL access. Uses composition pattern to wrap
 * ImageUploadService with a specific configuration.
 */
final class ImageUploadPublicService
{
    public function __construct(
        private ImageUploadService $service,
    ) {}

    /**
     * Delete an image from the public bucket by its relative path.
     *
     * @param string $path The relative path returned by upload() (e.g. "products/slug-abc123.jpg").
     */
    public function delete(string $path): void
    {
        $this->service->delete($path);
    }

    /**
     * Upload image to public bucket.
     *
     * @param UploadedFileInterface $file
     * @param string $subfolder
     * @param array<string>|null $allowedExtensions
     * @param int|null $maxSizeBytes
     * @param ImageDimensions|null $dimensions
     * @param string|null $customBaseName
     * @return StoredFile DTO with path and URL (CDN accessible)
     */
    public function upload(
        UploadedFileInterface $file,
        string $subfolder,
        ?array $allowedExtensions = null,
        ?int $maxSizeBytes = null,
        ?ImageDimensions $dimensions = null,
        ?string $customBaseName = null,
    ): StoredFile {
        return $this->service->upload(
            $file,
            $subfolder,
            $allowedExtensions,
            $maxSizeBytes,
            $dimensions,
            customBaseName: $customBaseName,
        );
    }
}
