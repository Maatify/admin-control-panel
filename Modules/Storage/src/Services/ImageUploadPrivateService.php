<?php

declare(strict_types=1);

namespace Maatify\Storage\Services;

use Maatify\Storage\Config\ImageDimensions;
use Maatify\Storage\DTO\StoredFile;
use Psr\Http\Message\UploadedFileInterface;

/**
 * ImageUploadService wrapper for private uploads (authenticated access).
 *
 * This service is configured via DI to upload images to a private bucket with
 * private ACL. Returns storage paths (not signed URLs directly).
 * Signed URLs must be generated separately using DigitalOcean Spaces API.
 *
 * Uses composition pattern to wrap ImageUploadService with a specific configuration.
 */
final class ImageUploadPrivateService
{
    public function __construct(
        private ImageUploadService $service,
    ) {}

    /**
     * Delete an image from the private bucket by its relative path.
     *
     * @param string $path The relative path returned by upload() (e.g. "private/user-123/avatar-abc123.jpg").
     */
    public function delete(string $path): void
    {
        $this->service->delete($path);
    }

    /**
     * Upload image to private bucket.
     *
     * @param UploadedFileInterface $file
     * @param string $subfolder
     * @param array<string>|null $allowedExtensions
     * @param int|null $maxSizeBytes
     * @param ImageDimensions|null $dimensions
     * @param string|null $customBaseName
     * @return StoredFile DTO with path and URL (requires signed URL for access)
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
