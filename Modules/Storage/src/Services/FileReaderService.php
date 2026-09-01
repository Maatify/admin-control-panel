<?php

declare(strict_types=1);

namespace Maatify\Storage\Services;

use Maatify\Storage\Contracts\ReaderAdapterInterface;
use Maatify\Storage\DTO\FileAccessResult;
use Maatify\Storage\DTO\FileMetadata;
use Maatify\Storage\Exception\FileNotFoundException;
use Maatify\Storage\Exception\InvalidPathException;
use Maatify\Storage\Exception\ReaderAdapterException;

/**
 * File reader service.
 *
 * Coordinates file reading operations across different storage backends.
 * Provides unified interface for accessing stored files whether they're on local
 * filesystem or DigitalOcean Spaces.
 *
 * **Usage Pattern**:
 * ```php
 * // Public file access (CDN or local path)
 * $result = $reader->getAccessUrl('images/product-abc123.jpg');
 * if ($result->isSigned) {
 *     // DO Spaces: Return presigned URL directly to client
 *     return response()->json(['url' => $result->url]);
 * } else {
 *     // Local: Application controls access
 *     return response()->file(storage_path('app/' . $result->url));
 * }
 *
 * // Private file access with expiration control
 * $result = $reader->getAccessUrl('invoices/user-5-2024.pdf', expirationMinutes: 30);
 * return response()->json(['downloadUrl' => $result->url]);
 *
 * // Audit metadata
 * $metadata = $reader->getMetadata('documents/contract.pdf');
 * logger()->info('File accessed', [
 *     'path' => 'documents/contract.pdf',
 *     'sizeBytes' => $metadata->sizeBytes,
 *     'lastModified' => $metadata->lastModified,
 * ]);
 * ```
 */
final class FileReaderService
{
    /**
     * @param ReaderAdapterInterface $adapter Storage adapter (local filesystem or DO Spaces)
     */
    public function __construct(
        private readonly ReaderAdapterInterface $adapter,
    ) {}

    /**
     * Get access URL or path for stored file.
     *
     * For public uploads (local):
     * - Returns relative path like "images/product-abc123.jpg"
     * - Application implements file streaming
     *
     * For public uploads (DO Spaces):
     * - Returns presigned URL valid for specified duration
     * - DO validates expiration server-side
     *
     * For private uploads (local):
     * - Returns relative path for signing via separate service
     * - Application generates signed URL for time-limited access
     *
     * For private uploads (DO Spaces):
     * - Returns presigned URL valid for specified duration
     * - URL is cryptographically signed and safe to return to client
     *
     * **Important**: Does NOT verify file existence (by design).
     * Call exists() separately if you need verification.
     *
     * @param string $path Storage path (relative, no leading slash)
     * @param int $expirationMinutes How long URL is valid (default: 60 minutes)
     *
     * @return FileAccessResult Encapsulates URL/path with validity metadata
     *
     * @throws InvalidPathException If path fails security validation
     * @throws ReaderAdapterException If adapter encounters an error
     */
    public function getAccessUrl(string $path, int $expirationMinutes = 60): FileAccessResult
    {
        $path = $this->validateAndNormalizePath($path);
        return $this->adapter->getAccessUrl($path, $expirationMinutes);
    }

    /**
     * Check if file exists in storage.
     *
     * Optional verification before attempting to access a file.
     * For public files, callers can assume file exists and catch FileNotFoundException
     * if it doesn't. For auditing/logging, this method provides explicit existence check.
     *
     * @param string $path Storage path (relative, no leading slash)
     *
     * @return bool true if file exists, false otherwise
     *
     * @throws InvalidPathException If path fails security validation
     * @throws ReaderAdapterException If adapter encounters an error
     */
    public function exists(string $path): bool
    {
        $path = $this->validateAndNormalizePath($path);
        return $this->adapter->exists($path);
    }

    /**
     * Get file metadata for auditing and analytics.
     *
     * Retrieves metadata about a stored file:
     * - Size in bytes for bandwidth/storage calculations
     * - Last modification time for cache invalidation
     * - ETag for change detection in CDN layers
     * - MIME type for content validation
     *
     * **Auditing Pattern**:
     * ```php
     * // Log file access for compliance
     * $metadata = $reader->getMetadata($path);
     * audit()->log('file_accessed', [
     *     'path' => $path,
     *     'sizeBytes' => $metadata->sizeBytes,
     *     'contentType' => $metadata->contentType,
     *     'lastModified' => $metadata->lastModified,
     * ]);
     * ```
     *
     * @param string $path Storage path (relative, no leading slash)
     *
     * @return FileMetadata File metadata (size, type, modification time, ETag)
     *
     * @throws InvalidPathException If path fails security validation
     * @throws FileNotFoundException If file does not exist
     * @throws ReaderAdapterException If adapter encounters an error
     */
    public function getMetadata(string $path): FileMetadata
    {
        $path = $this->validateAndNormalizePath($path);
        return $this->adapter->getMetadata($path);
    }

    /**
     * Centralized path validation and normalization.
     *
     * Enforces:
     * - No empty paths
     * - No leading slashes (relative keys only)
     * - No backslashes (forward-slash only)
     * - No null bytes
     * - No directory traversal (.. / ../)
     * - No encoded traversal (%2e%2e)
     * - No double slashes
     *
     * @param string $path Storage path to validate
     *
     * @return string Validated path (same as input if valid)
     *
     * @throws InvalidPathException If path fails any validation check
     */
    private function validateAndNormalizePath(string $path): string
    {
        // All validation is now centralized here - adapters also validate defensively
        // but this service layer enforces the boundary

        if ($path === '') {
            throw InvalidPathException::emptyPath();
        }

        // Reject leading slash - paths must be relative storage keys
        if (str_starts_with($path, '/')) {
            throw InvalidPathException::invalidPath($path);
        }

        // Reject backslashes - paths must use forward slashes only
        if (str_contains($path, '\\')) {
            throw InvalidPathException::invalidPath($path);
        }

        // Check for null bytes
        if (str_contains($path, "\0")) {
            throw InvalidPathException::containsNullBytes($path);
        }

        // Detect directory traversal (.., /../)
        if (str_contains($path, '..') || str_contains($path, '/../')) {
            throw InvalidPathException::containsTraversal($path);
        }

        // Detect encoded traversal attempts (double-decode safe)
        $decoded = $path;
        for ($i = 0; $i < 2; $i++) {
            $next = rawurldecode($decoded);
            if ($next === $decoded) {
                break;
            }
            $decoded = $next;
        }

        if (str_contains($decoded, '..') || str_contains($decoded, '/../')) {
            throw InvalidPathException::containsTraversal($path);
        }

        // Detect double slashes (except protocol://)
        if (preg_match('#(?<!:)//+#', $path)) {
            throw InvalidPathException::invalidPath($path);
        }

        return $path;
    }
}
