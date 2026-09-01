<?php

declare(strict_types=1);

namespace Maatify\Storage\Services;

use Maatify\Storage\DTO\FileAccessResult;
use Maatify\Storage\DTO\FileMetadata;

/**
 * Video reader service.
 *
 * Ready-to-use service for reading videos from storage.
 * Provides unified interface across storage backends (local filesystem or DigitalOcean Spaces).
 *
 * **Typical Usage**:
 * ```php
 * // Get public video URL for streaming
 * $result = $reader->getAccessUrl('courses/intro.mp4');
 * return response()->json(['videoUrl' => $result->url]);
 *
 * // Get private video with time-limited access
 * $result = $reader->getAccessUrl('paid-content/course-5.mp4', expirationMinutes: 120);
 * return response()->json(['videoUrl' => $result->url]);
 *
 * // Verify video exists and get metadata
 * $metadata = $reader->getMetadata('tutorials/setup.mp4');
 * return response()->json([
 *     'sizeBytes' => $metadata->sizeBytes,
 *     'contentType' => $metadata->contentType,
 * ]);
 * ```
 */
final class VideoReaderService
{
    public function __construct(
        private readonly FileReaderService $fileReader,
    ) {}

    /**
     * Get access URL for video.
     *
     * Returns appropriate URL based on storage type:
     * - Local: Relative path for application-controlled streaming
     * - DO Spaces: Full presigned URL (public or time-limited)
     *
     * @param string $path Video storage path (e.g., "courses/intro.mp4")
     * @param int $expirationMinutes URL validity duration (default: 60 minutes)
     *
     * @return FileAccessResult Contains URL/path and expiration metadata
     */
    public function getAccessUrl(string $path, int $expirationMinutes = 60): FileAccessResult
    {
        return $this->fileReader->getAccessUrl($path, $expirationMinutes);
    }

    /**
     * Check if video exists in storage.
     *
     * @param string $path Video storage path
     *
     * @return bool true if video exists
     */
    public function exists(string $path): bool
    {
        return $this->fileReader->exists($path);
    }

    /**
     * Get video metadata.
     *
     * @param string $path Video storage path
     *
     * @return FileMetadata Contains size, type, modification time
     */
    public function getMetadata(string $path): FileMetadata
    {
        return $this->fileReader->getMetadata($path);
    }
}
