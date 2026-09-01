<?php

declare(strict_types=1);

namespace Maatify\Storage\Services;

use Maatify\Storage\DTO\FileAccessResult;
use Maatify\Storage\DTO\FileMetadata;

/**
 * Public image reader service.
 *
 * Retrieves publicly-accessible images from storage.
 * For DigitalOcean Spaces: returns S3 presigned GetObject URLs.
 * For Local: returns relative paths for application-controlled streaming.
 *
 * **Typical Usage**:
 * ```php
 * // Product gallery
 * $imageResult = $this->imageReader->getAccessUrl('products/gallery/item-123.jpg');
 * return response()->json([
 *     'imageUrl' => $imageResult->url,  // Presigned URL or relative path
 * ]);
 *
 * // Audit file access
 * $metadata = $this->imageReader->getMetadata('products/gallery/item-123.jpg');
 * audit()->log('image_viewed', [
 *     'size' => $metadata->sizeBytes,
 *     'type' => $metadata->contentType,
 * ]);
 * ```
 */
final class ImageReaderPublicService
{
    /**
     * @param ImageReaderService $service Configured image reader with public image adapter
     */
    public function __construct(
        private readonly ImageReaderService $service,
    ) {}

    /**
     * Get public access URL for image.
     *
     * For DigitalOcean Spaces: returns S3 presigned GetObject URL.
     * For Local: returns relative path for application-controlled streaming.
     *
     * @param string $path Image storage path (e.g., "products/gallery/item-123.jpg")
     * @param int $expirationMinutes URL validity duration (default: 60 minutes)
     *
     * @return FileAccessResult Contains $url for client, $path for storage
     */
    public function getAccessUrl(string $path, int $expirationMinutes = 60): FileAccessResult
    {
        return $this->service->getAccessUrl($path, $expirationMinutes);
    }

    /**
     * Check if public image exists.
     *
     * @param string $path Image storage path
     *
     * @return bool true if image exists
     */
    public function exists(string $path): bool
    {
        return $this->service->exists($path);
    }

    /**
     * Get image metadata for auditing/analytics.
     *
     * @param string $path Image storage path
     *
     * @return FileMetadata Contains size, type, modification time
     */
    public function getMetadata(string $path): FileMetadata
    {
        return $this->service->getMetadata($path);
    }
}
