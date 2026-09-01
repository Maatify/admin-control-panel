<?php

declare(strict_types=1);

namespace Maatify\Storage\DTO;

use DateTimeImmutable;
use Maatify\SharedCommon\Contracts\ClockInterface;

/**
 * Access result returned by reader services and adapters.
 *
 * Encapsulates both the access URL/path and metadata about its validity.
 *
 * **For local storage:**
 * - $url: Relative path (e.g., "images/product-abc123.jpg")
 * - $isSigned: false (path not cryptographically signed)
 * - Application controls file access/streaming
 *
 * **For DigitalOcean Spaces:**
 * - $url: S3 presigned URL (e.g., "https://bucket.fra1.cdn.../...?X-Amz-Signature=...")
 * - $isSigned: true (URL is cryptographically signed by S3/DO)
 * - DO validates expiration server-side; application returns URL directly to client
 */
final readonly class FileAccessResult
{
    public function __construct(
        /**
         * Access URL or path.
         *
         * For local: relative path like "images/product-abc123.jpg"
         * For DO Spaces: full presigned URL like "https://bucket.fra1.cdn.../...?X-Amz-Signature=..."
         */
        public string $url,

        /**
         * Storage path (always relative, no leading slash).
         *
         * Example: "images/product-abc123.jpg"
         *
         * Use this for:
         * - Database storage (decouple from access mechanism)
         * - Signing/verification with separate signing service
         * - Debugging/logging
         */
        public string $path,

        /**
         * When this access URL/path expires and is no longer valid.
         *
         * For local: informational only; application enforces if needed.
         * For DO: DO Spaces validates server-side.
         */
        public DateTimeImmutable $expiresAt,

        /**
         * Duration of validity in seconds (e.g., 3600 for 1 hour).
         */
        public int $expirationSeconds,

        /**
         * Whether the URL is cryptographically signed.
         *
         * true = S3 presigned URL (DO Spaces) - safe to return directly to client
         * false = relative path (local) - application must handle access control
         */
        public bool $isSigned,
    ) {}

    /**
     * Check if this access result has expired.
     *
     * @return bool true if current time is after expiresAt
     */
    public function hasExpired(ClockInterface $clock): bool
    {
        return $clock->now() > $this->expiresAt;
    }
}
