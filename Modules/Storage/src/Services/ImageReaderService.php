<?php

declare(strict_types=1);

namespace Maatify\Storage\Services;

use Maatify\Storage\DTO\FileAccessResult;
use Maatify\Storage\DTO\FileMetadata;

/**
 * Image reader service.
 *
 * Ready-to-use service for reading images from storage.
 * Provides unified interface across storage backends (local filesystem or DigitalOcean Spaces).
 *
 * **Typical Usage**:
 * ```php
 * // Get public image URL
 * $result = $reader->getAccessUrl('products/gallery/item-123.jpg');
 * return response()->json(['imageUrl' => $result->url]);
 *
 * // Get private image with expiration
 * $result = $reader->getAccessUrl('users/profiles/user-5.jpg', expirationMinutes: 30);
 * return response()->json(['imageUrl' => $result->url]);
 *
 * // Check existence before accessing
 * if ($reader->exists('products/featured.jpg')) {
 *     $metadata = $reader->getMetadata('products/featured.jpg');
 * }
 * ```
 */
final class ImageReaderService
{
    public function __construct(
        private readonly FileReaderService $fileReader,
    ) {}

    /**
     * Get access URL for image.
     *
     * Returns appropriate URL based on storage type:
     * - Local: Relative path for application-controlled streaming
     * - DO Spaces: Full presigned URL (public or time-limited)
     *
     * @param string $path Image storage path (e.g., "products/gallery/item-123.jpg")
     * @param int $expirationMinutes URL validity duration (default: 60 minutes)
     *
     * @return FileAccessResult Contains URL/path and expiration metadata
     */
    public function getAccessUrl(string $path, int $expirationMinutes = 60): FileAccessResult
    {
        return $this->fileReader->getAccessUrl($path, $expirationMinutes);
    }

    /**
     * Check if image exists in storage.
     *
     * @param string $path Image storage path
     *
     * @return bool true if image exists
     */
    public function exists(string $path): bool
    {
        return $this->fileReader->exists($path);
    }

    /**
     * Get image metadata.
     *
     * @param string $path Image storage path
     *
     * @return FileMetadata Contains size, type, modification time
     */
    public function getMetadata(string $path): FileMetadata
    {
        return $this->fileReader->getMetadata($path);
    }
}
