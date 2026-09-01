<?php

declare(strict_types=1);

namespace Maatify\Storage\Services;

use Maatify\Storage\DTO\StoredFile;
use Psr\Http\Message\UploadedFileInterface;

/**
 * VideoUploadService wrapper for public uploads (CDN accessible).
 *
 * This service is configured via DI to upload videos to a public bucket with
 * public-read ACL and CDN URL access. Uses composition pattern to wrap
 * VideoUploadService with a specific configuration.
 */
final class VideoUploadPublicService
{
    public function __construct(
        private VideoUploadService $service,
    ) {}

    /**
     * Delete a video from the public bucket by its relative path.
     *
     * @param string $path The relative path returned by upload() (e.g. "tutorials/tutorial-abc123.mp4").
     */
    public function delete(string $path): void
    {
        $this->service->delete($path);
    }

    /**
     * Upload video to public bucket.
     *
     * @param UploadedFileInterface $file
     * @param string $subfolder
     * @param array<string>|null $allowedExtensions
     * @param int|null $maxSizeBytes
     * @param string|null $customBaseName
     * @return StoredFile DTO with path and URL (CDN accessible)
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
