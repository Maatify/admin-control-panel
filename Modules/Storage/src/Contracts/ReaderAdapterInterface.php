<?php

declare(strict_types=1);

namespace Maatify\Storage\Contracts;

use Maatify\Storage\DTO\FileAccessResult;
use Maatify\Storage\DTO\FileMetadata;
use Maatify\Storage\Exception\FileNotFoundException;
use Maatify\Storage\Exception\InvalidPathException;
use Maatify\Storage\Exception\ReaderAdapterException;

/**
 * Reader adapter contract for retrieving stored files.
 *
 * Provides unified interface for accessing files across different storage backends:
 * - Local filesystem: Returns relative paths for application-controlled access
 * - DigitalOcean Spaces: Returns S3-compatible presigned URLs (signed by DO)
 *
 * **Example Usage**:
 * ```php
 * // Public file access
 * $result = $adapter->getAccessUrl('images/product-abc123.jpg');
 * // Local: returns FileAccessResult with path="images/product-abc123.jpg", isSigned=false
 * // DO: returns FileAccessResult with presigned URL, isSigned=true
 *
 * // Private file (time-limited access)
 * $result = $adapter->getAccessUrl('invoices/user-5-2024.pdf', expirationMinutes: 15);
 * // Local: returns relative path, application must sign separately
 * // DO: returns presigned URL valid for 15 minutes
 * ```
 */
interface ReaderAdapterInterface
{
    /**
     * Generate access URL or path for stored file.
     *
     * For local storage:
     * - Returns relative path like "images/product-abc123.jpg"
     * - Application controls access/streaming
     * - isSigned=false (path is not cryptographically signed)
     *
     * For DigitalOcean Spaces:
     * - Returns S3-compatible presigned URL with signature
     * - DO validates expiration server-side
     * - isSigned=true (URL is cryptographically signed by S3/DO)
     *
     * @param string $path Storage path (relative, e.g., "images/product-abc123.jpg")
     * @param int $expirationMinutes Validity duration in minutes (default: 60)
     *
     * @return FileAccessResult Encapsulates URL/path and metadata about validity
     *
     * @throws InvalidPathException If path is empty, contains traversal attempts, or has null bytes
     * @throws ReaderAdapterException If adapter cannot generate access URL (e.g., S3 API error)
     *
     * Note: This method does NOT verify file existence by default. To verify before generating
     * access URL, call exists() separately or rely on the adapter's implementation-specific behavior.
     */
    public function getAccessUrl(string $path, int $expirationMinutes = 60): FileAccessResult;

    /**
     * Check if file exists in storage.
     *
     * For local storage: Checks filesystem with file_exists()
     * For DigitalOcean Spaces: Makes HEAD request to S3 API
     *
     * This is an optional verification method. Most access control is delegated to:
     * - Local: Application-enforced file streaming
     * - DO Spaces: ACL and presigned URL expiration validation
     *
     * @param string $path Storage path (relative, e.g., "images/product-abc123.jpg")
     *
     * @return bool true if file exists, false otherwise
     *
     * @throws InvalidPathException If path is empty, contains traversal attempts, or has null bytes
     * @throws ReaderAdapterException If adapter cannot verify existence (e.g., S3 API error)
     */
    public function exists(string $path): bool;

    /**
     * Get file metadata for auditing and analytics.
     *
     * Retrieves:
     * - sizeBytes: File size in bytes
     * - lastModified: Last modification timestamp (null if not available)
     * - eTag: Entity tag for change detection (null if not available)
     * - contentType: MIME type like 'image/jpeg' (null if not available)
     *
     * For local storage: Reads from filesystem (fstat, mime_content_type)
     * For DigitalOcean Spaces: Fetches from S3 HEAD response
     *
     * @param string $path Storage path (relative, e.g., "images/product-abc123.jpg")
     *
     * @return FileMetadata File metadata (size, type, modification time, ETag)
     *
     * @throws FileNotFoundException If file does not exist
     * @throws InvalidPathException If path is empty, contains traversal attempts, or has null bytes
     * @throws ReaderAdapterException If adapter cannot fetch metadata (e.g., S3 API error)
     */
    public function getMetadata(string $path): FileMetadata;
}
