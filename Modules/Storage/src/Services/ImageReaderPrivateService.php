<?php

declare(strict_types=1);

namespace Maatify\Storage\Services;

use Maatify\Storage\DTO\FileAccessResult;
use Maatify\Storage\DTO\FileMetadata;

/**
 * Private image reader service.
 *
 * Retrieves private images from storage with time-limited access control.
 * Supports temporary signed URLs for restricted viewing (e.g., user profile images).
 *
 * **Typical Usage**:
 * ```php
 * // User profile image with 30-minute expiration
 * $imageResult = $this->imageReader->getAccessUrl('users/profiles/user-5.jpg', expirationMinutes: 30);
 * return response()->json([
 *     'profileImageUrl' => $imageResult->url,  // Signed URL valid for 30 minutes
 * ]);
 *
 * // Generate long-lived signed URL for client-side download
 * $imageResult = $this->imageReader->getAccessUrl('users/profiles/user-5.jpg', expirationMinutes: 1440);
 * return response()->download($imageResult->url);
 * ```
 */
final class ImageReaderPrivateService
{
    /**
     * @param ImageReaderService $service Configured image reader with private image adapter
     */
    public function __construct(
        private readonly ImageReaderService $service,
    ) {}

    /**
     * Get private access URL for image with expiration.
     *
     * For DigitalOcean Spaces: returns signed S3 presigned URL.
     * For Local: returns relative path; application-level signing is separate.
     *
     * @param string $path Image storage path (e.g., "users/profiles/user-5.jpg")
     * @param int $expirationMinutes How long URL remains valid (default: 60 minutes)
     *
     * @return FileAccessResult Contains URL/path with expiration metadata
     */
    public function getAccessUrl(string $path, int $expirationMinutes = 60): FileAccessResult
    {
        return $this->service->getAccessUrl($path, $expirationMinutes);
    }

    /**
     * Check if private image exists.
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
