<?php

declare(strict_types=1);

namespace Maatify\Storage\Services;

use Maatify\Storage\DTO\StoredFile;
use Psr\Http\Message\UploadedFileInterface;

/**
 * AudioUploadService wrapper for private uploads (authenticated access).
 *
 * This service is configured via DI to upload audio files to a private bucket with
 * private ACL. Returns storage paths (not signed URLs directly).
 * Signed URLs must be generated separately using DigitalOcean Spaces API.
 *
 * Uses composition pattern to wrap AudioUploadService with a specific configuration.
 */
final class AudioUploadPrivateService
{
    public function __construct(
        private AudioUploadService $service,
    ) {}

    /**
     * Delete an audio file from the private bucket by its relative path.
     *
     * @param string $path The relative path returned by upload() (e.g. "private-audio/user-123/dua-abc123.mp3").
     */
    public function delete(string $path): void
    {
        $this->service->delete($path);
    }

    /**
     * Upload audio to private bucket.
     *
     * @param UploadedFileInterface $file
     * @param string $subfolder
     * @param array<string>|null $allowedExtensions
     * @param int|null $maxSizeBytes
     * @param string|null $customBaseName
     * @return StoredFile DTO with path and URL (requires signed URL for access)
     */
    public function upload(
        UploadedFileInterface $file,
        string $subfolder,
        ?array $allowedExtensions = null,
        ?int $maxSizeBytes = null,
        ?string $customBaseName = null,
    ): StoredFile {
        return $this->service->upload(
            $file,
            $subfolder,
            $allowedExtensions,
            $maxSizeBytes,
            customBaseName: $customBaseName,
        );
    }
}
