<?php

declare(strict_types=1);

namespace Maatify\Storage\Services;

use Maatify\Storage\DTO\FileAccessResult;
use Maatify\Storage\DTO\FileMetadata;

/**
 * Public video reader service.
 *
 * Retrieves publicly-accessible videos from storage.
 * For DigitalOcean Spaces: returns S3 presigned GetObject URLs.
 * For Local: returns relative paths for application-controlled streaming.
 *
 * **Typical Usage**:
 * ```php
 * // Tutorial video
 * $videoResult = $this->videoReader->getAccessUrl('tutorials/getting-started.mp4');
 * return response()->json([
 *     'videoUrl' => $videoResult->url,  // Presigned URL or relative path
 *     'duration' => getVideoDuration($videoResult->path),
 * ]);
 *
 * // Track video playback
 * $metadata = $this->videoReader->getMetadata('tutorials/getting-started.mp4');
 * analytics()->track('video_played', [
 *     'sizeBytes' => $metadata->sizeBytes,
 *     'mimeType' => $metadata->contentType,
 * ]);
 * ```
 */
final class VideoReaderPublicService
{
    /**
     * @param VideoReaderService $service Configured video reader with public video adapter
     */
    public function __construct(
        private readonly VideoReaderService $service,
    ) {}

    /**
     * Get public access URL for video.
     *
     * For DigitalOcean Spaces: returns S3 presigned GetObject URL.
     * For Local: returns relative path for application-controlled streaming.
     *
     * @param string $path Video storage path (e.g., "tutorials/getting-started.mp4")
     * @param int $expirationMinutes URL validity duration (default: 60 minutes)
     *
     * @return FileAccessResult Contains $url for player, $path for storage
     */
    public function getAccessUrl(string $path, int $expirationMinutes = 60): FileAccessResult
    {
        return $this->service->getAccessUrl($path, $expirationMinutes);
    }

    /**
     * Check if public video exists.
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
     * Get video metadata for analytics.
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
