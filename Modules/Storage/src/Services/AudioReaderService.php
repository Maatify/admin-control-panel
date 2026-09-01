<?php

declare(strict_types=1);

namespace Maatify\Storage\Services;

use Maatify\Storage\DTO\FileAccessResult;
use Maatify\Storage\DTO\FileMetadata;

/**
 * Audio reader service.
 *
 * Ready-to-use service for reading audio files from storage.
 * Provides unified interface across storage backends (local filesystem or DigitalOcean Spaces).
 *
 * **Typical Usage**:
 * ```php
 * // Get public audio URL for streaming
 * $result = $reader->getAccessUrl('podcasts/episode-5.mp3');
 * return response()->json(['audioUrl' => $result->url]);
 *
 * // Get private audio with time-limited access
 * $result = $reader->getAccessUrl('voice-messages/user-5.wav', expirationMinutes: 60);
 * return response()->json(['audioUrl' => $result->url]);
 *
 * // Get audio metadata for duration calculation
 * $metadata = $reader->getMetadata('music/track-1.aac');
 * return response()->json([
 *     'sizeBytes' => $metadata->sizeBytes,
 *     'contentType' => $metadata->contentType,
 * ]);
 * ```
 */
final class AudioReaderService
{
    public function __construct(
        private readonly FileReaderService $fileReader,
    ) {}

    /**
     * Get access URL for audio file.
     *
     * Returns appropriate URL based on storage type:
     * - Local: Relative path for application-controlled streaming
     * - DO Spaces: Full presigned URL (public or time-limited)
     *
     * @param string $path Audio storage path (e.g., "podcasts/episode-5.mp3")
     * @param int $expirationMinutes URL validity duration (default: 60 minutes)
     *
     * @return FileAccessResult Contains URL/path and expiration metadata
     */
    public function getAccessUrl(string $path, int $expirationMinutes = 60): FileAccessResult
    {
        return $this->fileReader->getAccessUrl($path, $expirationMinutes);
    }

    /**
     * Check if audio file exists in storage.
     *
     * @param string $path Audio storage path
     *
     * @return bool true if audio file exists
     */
    public function exists(string $path): bool
    {
        return $this->fileReader->exists($path);
    }

    /**
     * Get audio file metadata.
     *
     * @param string $path Audio storage path
     *
     * @return FileMetadata Contains size, type, modification time
     */
    public function getMetadata(string $path): FileMetadata
    {
        return $this->fileReader->getMetadata($path);
    }
}
