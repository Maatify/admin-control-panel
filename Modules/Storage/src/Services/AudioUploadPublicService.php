<?php

declare(strict_types=1);

namespace Maatify\Storage\Services;

use Maatify\Storage\DTO\StoredFile;
use Psr\Http\Message\UploadedFileInterface;

/**
 * AudioUploadService wrapper for public uploads (CDN accessible).
 *
 * This service is configured via DI to upload audio files to a public bucket with
 * public-read ACL and CDN URL access. Uses composition pattern to wrap
 * AudioUploadService with a specific configuration.
 */
final class AudioUploadPublicService
{
    public function __construct(
        private AudioUploadService $service,
    ) {}

    /**
     * Delete an audio file from the public bucket by its relative path.
     *
     * @param string $path The relative path returned by upload() (e.g. "podcasts/episode-5-abc123.mp3").
     */
    public function delete(string $path): void
    {
        $this->service->delete($path);
    }

    /**
     * Upload audio to public bucket.
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
