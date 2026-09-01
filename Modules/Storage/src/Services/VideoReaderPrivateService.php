<?php

declare(strict_types=1);

namespace Maatify\Storage\Services;

use Maatify\Storage\DTO\FileAccessResult;
use Maatify\Storage\DTO\FileMetadata;

/**
 * Private video reader service.
 *
 * Retrieves private videos from storage with time-limited access control.
 * Supports temporary signed URLs for restricted viewing (e.g., course videos).
 *
 * **Typical Usage**:
 * ```php
 * // Course video with 24-hour expiration for enrolled users
 * $videoResult = $this->videoReader->getAccessUrl('courses/course-5/module-2.mp4', expirationMinutes: 1440);
 * return response()->json([
 *     'videoUrl' => $videoResult->url,  // Signed URL valid for 24 hours
 *     'expiresAt' => $videoResult->expiresAt,
 * ]);
 *
 * // User-generated video preview
 * $videoResult = $this->videoReader->getAccessUrl('user-uploads/user-5-intro.mp4', expirationMinutes: 10);
 * return response()->json(['previewUrl' => $videoResult->url]);
 * ```
 */
final class VideoReaderPrivateService
{
    /**
     * @param VideoReaderService $service Configured video reader with private video adapter
     */
    public function __construct(
        private readonly VideoReaderService $service,
    ) {}

    /**
     * Get private access URL for video with expiration.
     *
     * For DigitalOcean Spaces: returns signed S3 presigned URL.
     * For Local: returns relative path; application-level signing is separate.
     *
     * @param string $path Video storage path (e.g., "courses/course-5/module-2.mp4")
     * @param int $expirationMinutes How long URL remains valid (default: 60 minutes)
     *
     * @return FileAccessResult Contains URL/path with expiration metadata
     */
    public function getAccessUrl(string $path, int $expirationMinutes = 60): FileAccessResult
    {
        return $this->service->getAccessUrl($path, $expirationMinutes);
    }

    /**
     * Check if private video exists.
     *
     * @param string $path Video storage path
     *
     * @return bool true if video exists
     */
    public function exists(string $path): bool
    {
        return $this->service->exists($path);
    }

    /**
     * Get video metadata for auditing.
     *
     * @param string $path Video storage path
     *
     * @return FileMetadata Contains size, type, modification time
     */
    public function getMetadata(string $path): FileMetadata
    {
        return $this->service->getMetadata($path);
    }
}
