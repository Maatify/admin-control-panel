<?php

declare(strict_types=1);

namespace Maatify\Storage\DTO;

/**
 * Represents a stored file after successful upload.
 *
 * This DTO encapsulates both the storage path and the resolved URL, providing
 * flexibility for different use cases:
 *
 * ✅ Public uploads: $url is the full CDN URL (ready to use directly)
 * 🔒 Private uploads: $url is the storage path (use with DigitalOcean Spaces API to generate signed URLs)
 *
 * This pattern follows professional standards used by AWS S3, Google Cloud Storage,
 * and Azure Storage SDKs, ensuring maximum flexibility for callers.
 *
 * @example
 * // Public image upload - use CDN URL directly
 * $storedFile = $imageService->upload($file, 'products');
 * return response()->json(['url' => $storedFile->url]);
 *
 * @example
 * // Private video upload - use path for signed URL generation
 * $storedFile = $videoService->upload($file, 'user-videos');
 * $signedUrl = $this->generateSignedUrl($storedFile->path);
 * return response()->json(['signedUrl' => $signedUrl]);
 */
final class StoredFile
{
    public function __construct(
        /**
         * Storage key / relative path of the uploaded file (without leading slash).
         * Use this with storage backends to generate signed URLs or retrieve the file.
         * Example: podcasts/episode-5-abc123def456.mp3
         */
        public readonly string $path,

        /**
         * Resolved URL of the uploaded file.
         *
         * For public uploads: Full CDN URL (e.g., https://cdn.example.com/podcasts/episode-5-abc123def456.mp3)
         * For private uploads: Storage path with leading slash for signed URL generation (e.g., /private-files/user-123/document-xyz.pdf)
         */
        public readonly string $url,
    ) {}
}
