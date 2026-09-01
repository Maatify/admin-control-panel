<?php

declare(strict_types=1);

namespace Maatify\Storage\Services;

use Maatify\Storage\DTO\FileAccessResult;
use Maatify\Storage\DTO\FileMetadata;

/**
 * Public audio reader service.
 *
 * Retrieves publicly-accessible audio files from storage.
 * For DigitalOcean Spaces: returns S3 presigned GetObject URLs.
 * For Local: returns relative paths for application-controlled streaming.
 *
 * **Typical Usage**:
 * ```php
 * // Podcast episode
 * $audioResult = $this->audioReader->getAccessUrl('podcasts/episode-42.mp3');
 * return response()->json([
 *     'audioUrl' => $audioResult->url,  // Presigned URL or relative path
 *     'duration' => getAudioDuration($audioResult->path),
 * ]);
 *
 * // Track audio playback
 * $metadata = $this->audioReader->getMetadata('podcasts/episode-42.mp3');
 * analytics()->track('episode_played', [
 *     'sizeBytes' => $metadata->sizeBytes,
 *     'contentType' => $metadata->contentType,
 * ]);
 * ```
 */
final class AudioReaderPublicService
{
    /**
     * @param AudioReaderService $service Configured audio reader with public audio adapter
     */
    public function __construct(
        private readonly AudioReaderService $service,
    ) {}

    /**
     * Get public access URL for audio file.
     *
     * For DigitalOcean Spaces: returns S3 presigned GetObject URL.
     * For Local: returns relative path for application-controlled streaming.
     *
     * @param string $path Audio storage path (e.g., "podcasts/episode-42.mp3")
     * @param int $expirationMinutes URL validity duration (default: 60 minutes)
     *
     * @return FileAccessResult Contains $url for player, $path for storage
     */
    public function getAccessUrl(string $path, int $expirationMinutes = 60): FileAccessResult
    {
        return $this->service->getAccessUrl($path, $expirationMinutes);
    }

    /**
     * Check if public audio file exists.
     *
     * @param string $path Audio storage path
     *
     * @return bool true if audio exists
     */
    public function exists(string $path): bool
    {
        return $this->service->exists($path);
    }

    /**
     * Get audio metadata for analytics.
     *
     * @param string $path Audio storage path
     *
     * @return FileMetadata Contains size, type, modification time
     */
    public function getMetadata(string $path): FileMetadata
    {
        return $this->service->getMetadata($path);
    }
}
