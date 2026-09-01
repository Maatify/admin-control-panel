<?php

declare(strict_types=1);

namespace Maatify\Storage\Adapters;

use DateTimeImmutable;
use Maatify\SharedCommon\Contracts\ClockInterface;
use Maatify\Storage\Contracts\ReaderAdapterInterface;
use Maatify\Storage\DTO\FileAccessResult;
use Maatify\Storage\DTO\FileMetadata;
use Maatify\Storage\Exception\FileNotFoundException;
use Maatify\Storage\Exception\InvalidPathException;
use Maatify\Storage\Exception\ReaderAdapterException;

/**
 * Local filesystem reader adapter.
 *
 * Returns relative paths for files stored on local filesystem.
 * Application is responsible for access control and file streaming.
 *
 * All file paths are:
 * - Validated against traversal attempts (.., /../, encoded variants)
 * - Validated against null bytes
 * - Returned as relative paths (no leading slash)
 * - Marked as unsigned (isSigned=false) since application handles access control
 */
final class LocalStorageReader implements ReaderAdapterInterface
{
    /**
     * @param string $storagePath Base directory for stored files (e.g., /var/storage/app)
     */
    public function __construct(
        private readonly string $storagePath,
        private readonly ClockInterface $clock,
    ) {}

    /**
     * Get access URL/path for local file.
     *
     * Returns relative path for application-controlled access.
     * Application must implement file streaming/access control.
     *
     * @throws InvalidPathException
     * @throws ReaderAdapterException
     */
    public function getAccessUrl(string $path, int $expirationMinutes = 60): FileAccessResult
    {
        $this->validatePath($path);

        // Note: Does NOT verify file existence (by design)
        // Application should call exists() separately if needed

        $expiresAt = $this->clock->now()->modify("+{$expirationMinutes} minutes");

        return new FileAccessResult(
            url: $path,  // Relative path for local storage
            path: $path,
            expiresAt: $expiresAt,
            expirationSeconds: $expirationMinutes * 60,
            isSigned: false,  // Application handles access control
        );
    }

    /**
     * Check if file exists in local storage.
     *
     * @throws InvalidPathException
     */
    public function exists(string $path): bool
    {
        $this->validatePath($path);
        return is_file($this->resolveAbsolutePath($path));
    }

    /**
     * Get metadata for local file.
     *
     * Reads size, modification time, and MIME type from filesystem.
     *
     * @throws FileNotFoundException
     * @throws InvalidPathException
     * @throws ReaderAdapterException
     */
    public function getMetadata(string $path): FileMetadata
    {
        $this->validatePath($path);

        $absolutePath = $this->resolveAbsolutePath($path);

        if (!is_file($absolutePath)) {
            throw FileNotFoundException::notFound($path);
        }

        if (!is_readable($absolutePath)) {
            throw ReaderAdapterException::adapterError("File is not readable: {$path}");
        }

        try {
            $sizeBytes = filesize($absolutePath);
            if ($sizeBytes === false) {
                throw ReaderAdapterException::adapterError("Cannot determine file size: {$path}");
            }

            $lastModified = filemtime($absolutePath);
            $lastModifiedDateTime = $lastModified !== false
                ? new DateTimeImmutable('@' . $lastModified)
                : null;

            $contentType = $this->resolveMimeType($absolutePath);

            return new FileMetadata(
                sizeBytes: $sizeBytes,
                lastModified: $lastModifiedDateTime,
                eTag: null,  // Local filesystem doesn't provide ETags
                contentType: $contentType,
            );
        } catch (ReaderAdapterException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw ReaderAdapterException::fromThrowable($e);
        }
    }

    /**
     * Validate storage path for security issues.
     *
     * Prevents:
     * - Empty paths
     * - Directory traversal attempts (.., /../, etc.)
     * - Encoded traversal attempts (%2e%2e, etc.)
     * - Null bytes
     * - Double slashes (path normalization)
     *
     * @throws InvalidPathException
     */
    private function validatePath(string $path): void
    {
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
    }


    /**
     * Resolve relative path to absolute filesystem path.
     *
     * Used by exists() and getMetadata() to access the actual filesystem.
     * Path validation is already done by caller.
     */
    private function resolveAbsolutePath(string $path): string
    {
        $storagePath = rtrim($this->storagePath, '/');
        return $storagePath . '/' . $path;
    }

    /**
     * Resolve MIME type from file extension or magic bytes.
     *
     * Falls back to application/octet-stream if type cannot be determined.
     */
    private function resolveMimeType(string $filePath): ?string
    {
        // Try finfo first (magic bytes detection)
        if (function_exists('finfo_file')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                // finfo objects are freed automatically (PHP 8.1+) — an explicit
                // finfo_close() call is deprecated as of PHP 8.5.
                $mimeType = finfo_file($finfo, $filePath);

                if ($mimeType !== false) {
                    return $mimeType;
                }
            }
        }

        // Fallback to mime_content_type (deprecated but still available)
        if (function_exists('mime_content_type')) {
            $mimeType = mime_content_type($filePath);
            if ($mimeType !== false) {
                return $mimeType;
            }
        }

        // Fallback to extension-based detection
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        return $this->getMimeTypeByExtension($extension);
    }

    /**
     * Get MIME type by file extension (fallback).
     */
    private function getMimeTypeByExtension(string $extension): ?string
    {
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'mov' => 'video/quicktime',
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'aac' => 'audio/aac',
            'ogg' => 'audio/ogg',
            'pdf' => 'application/pdf',
            'txt' => 'text/plain',
            'csv' => 'text/csv',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'zip' => 'application/zip',
        ];

        return $mimeTypes[$extension] ?? null;
    }
}
