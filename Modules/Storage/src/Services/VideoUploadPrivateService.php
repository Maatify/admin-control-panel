<?php

declare(strict_types=1);

namespace Maatify\Storage\Services;

use Maatify\Storage\DTO\StoredFile;
use Psr\Http\Message\UploadedFileInterface;

/**
 * VideoUploadService wrapper for private uploads (authenticated access).
 *
 * This service is configured via DI to upload videos to a private bucket with
 * private ACL. Returns storage paths (not signed URLs directly).
 * Signed URLs must be generated separately using DigitalOcean Spaces API.
 *
 * Uses composition pattern to wrap VideoUploadService with a specific configuration.
 */
final class VideoUploadPrivateService
{
    public function __construct(
        private VideoUploadService $service,
    ) {}

    /**
     * Delete a video from the private bucket by its relative path.
     *
     * @param string $path The relative path returned by upload() (e.g. "user-videos/user-123/video-abc123.mp4").
     */
    public function delete(string $path): void
    {
        $this->service->delete($path);
    }

    /**
     * Upload video to private bucket.
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
