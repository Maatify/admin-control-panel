<?php

declare(strict_types=1);

namespace Maatify\Storage\Services;

use Maatify\Storage\DTO\FileAccessResult;
use Maatify\Storage\DTO\FileMetadata;

/**
 * Private audio reader service.
 *
 * Retrieves private audio files from storage with time-limited access control.
 * Supports temporary signed URLs for restricted listening (e.g., course audio).
 *
 * **Typical Usage**:
 * ```php
 * // Audio lesson with 48-hour expiration
 * $audioResult = $this->audioReader->getAccessUrl('lessons/lesson-5-audio.mp3', expirationMinutes: 2880);
 * return response()->json([
 *     'audioUrl' => $audioResult->url,  // Signed URL valid for 48 hours
 *     'expiresAt' => $audioResult->expiresAt,
 * ]);
 *
 * // User-recorded voice message
 * $audioResult = $this->audioReader->getAccessUrl('voice-messages/user-5-message-2024.mp3', expirationMinutes: 30);
 * return response()->json(['messageUrl' => $audioResult->url]);
 * ```
 */
final class AudioReaderPrivateService
{
    /**
     * @param AudioReaderService $service Configured audio reader with private audio adapter
     */
    public function __construct(
        private readonly AudioReaderService $service,
    ) {}

    /**
     * Get private access URL for audio file with expiration.
     *
     * For DigitalOcean Spaces: returns signed S3 presigned URL.
     * For Local: returns relative path; application-level signing is separate.
     *
     * @param string $path Audio storage path (e.g., "lessons/lesson-5-audio.mp3")
     * @param int $expirationMinutes How long URL remains valid (default: 60 minutes)
     *
     * @return FileAccessResult Contains URL/path with expiration metadata
     */
    public function getAccessUrl(string $path, int $expirationMinutes = 60): FileAccessResult
    {
        return $this->service->getAccessUrl($path, $expirationMinutes);
    }

    /**
     * Check if private audio file exists.
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
     * Get audio metadata for auditing.
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
