<?php

declare(strict_types=1);

namespace Maatify\Storage\Adapters;

use Aws\Exception\AwsException;
use Aws\S3\S3Client;
use DateTimeImmutable;
use Maatify\SharedCommon\Contracts\ClockInterface;
use Maatify\Storage\Contracts\ReaderAdapterInterface;
use Maatify\Storage\DTO\FileAccessResult;
use Maatify\Storage\DTO\FileMetadata;
use Maatify\Storage\Exception\FileNotFoundException;
use Maatify\Storage\Exception\InvalidPathException;
use Maatify\Storage\Exception\ReaderAdapterException;

/**
 * DigitalOcean Spaces reader adapter.
 *
 * Generates S3-compatible presigned URLs for accessing files in DigitalOcean Spaces.
 * DO validates URL expiration server-side; URLs are signed and safe to return directly to clients.
 *
 * All file paths are:
 * - Validated against traversal attempts (.., /../, encoded variants)
 * - Validated against null bytes
 * - Returned as relative paths (no leading slash for object keys)
 * - Marked as signed (isSigned=true) with cryptographic signature from DO
 */
final class DOSpacesStorageReader implements ReaderAdapterInterface
{
    /**
     * @param S3Client $client Configured AWS S3 client for DigitalOcean Spaces
     * @param string $bucket Bucket name (e.g., "my-app-bucket")
     */
    public function __construct(
        private readonly S3Client $client,
        private readonly string $bucket,
        private readonly ClockInterface $clock,
    ) {}

    /**
     * Generate presigned URL for file in DigitalOcean Spaces.
     *
     * Returns S3-compatible presigned URL signed with AWS Signature V4.
     * DO validates expiration server-side; URL is safe to return directly to client.
     *
     * @throws InvalidPathException
     * @throws FileNotFoundException
     * @throws ReaderAdapterException
     */
    public function getAccessUrl(string $path, int $expirationMinutes = 60): FileAccessResult
    {
        $this->validatePath($path);

        // Object key: no leading slash for S3
        $objectKey = ltrim($path, '/');

        try {
            // Generate presigned URL (does NOT verify existence by design)
            // Application should call exists() separately if needed
            $cmd = $this->client->getCommand('GetObject', [
                'Bucket' => $this->bucket,
                'Key'    => $objectKey,
            ]);

            $request = $this->client->createPresignedRequest($cmd, "+{$expirationMinutes} minutes");
            $presignedUrl = (string)$request->getUri();

            // Note: Do NOT replace host - presigned URLs are signed for specific endpoint
            // Keep S3 endpoint as-is to preserve signature validity

            $expiresAt = $this->clock->now()->modify("+{$expirationMinutes} minutes");

            return new FileAccessResult(
                url: $presignedUrl,  // Full presigned URL (S3 endpoint, signature valid)
                path: $path,         // Original relative path
                expiresAt: $expiresAt,
                expirationSeconds: $expirationMinutes * 60,
                isSigned: true,  // URL is cryptographically signed by DO
            );
        } catch (InvalidPathException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw ReaderAdapterException::fromThrowable($e);
        }
    }

    /**
     * Check if object exists in DigitalOcean Spaces.
     *
     * Makes a HEAD request to S3 API to verify existence.
     *
     * @throws InvalidPathException
     * @throws ReaderAdapterException
     */
    public function exists(string $path): bool
    {
        $this->validatePath($path);
        $objectKey = ltrim($path, '/');

        try {
            $this->client->headObject([
                'Bucket' => $this->bucket,
                'Key'    => $objectKey,
            ]);
            return true;
        } catch (InvalidPathException $e) {
            throw $e;
        } catch (AwsException $e) {
            // 404 means object not found
            if ($e->getStatusCode() === 404) {
                return false;
            }
            // Other AWS errors
            throw ReaderAdapterException::fromThrowable($e);
        } catch (\Throwable $e) {
            throw ReaderAdapterException::fromThrowable($e);
        }
    }

    /**
     * Get metadata for object in DigitalOcean Spaces.
     *
     * Fetches size, modification time, ETag, and content type from S3 HEAD response.
     * Single HEAD request - no double-calling.
     *
     * @throws FileNotFoundException
     * @throws InvalidPathException
     * @throws ReaderAdapterException
     */
    public function getMetadata(string $path): FileMetadata
    {
        $this->validatePath($path);

        $objectKey = ltrim($path, '/');

        try {
            // Single HEAD request - will throw AwsException with 404 if not found
            $response = $this->client->headObject([
                'Bucket' => $this->bucket,
                'Key'    => $objectKey,
            ]);

            // Safely extract and cast response values
            $sizeBytes = 0;
            if (isset($response['ContentLength'])) {
                $contentLength = $response['ContentLength'];
                if (is_int($contentLength)) {
                    $sizeBytes = $contentLength;
                } elseif (is_numeric($contentLength)) {
                    $sizeBytes = (int)$contentLength;
                }
            }

            // Handle LastModified - can be DateTimeInterface or string
            $lastModified = null;
            if (isset($response['LastModified'])) {
                $rawLastModified = $response['LastModified'];
                if ($rawLastModified instanceof \DateTimeInterface) {
                    $lastModified = DateTimeImmutable::createFromInterface($rawLastModified);
                } elseif (is_string($rawLastModified)) {
                    $lastModified = new DateTimeImmutable($rawLastModified);
                }
            }

            $eTag = null;
            if (isset($response['ETag']) && is_string($response['ETag'])) {
                $eTag = $response['ETag'];
            }

            $contentType = null;
            if (isset($response['ContentType']) && is_string($response['ContentType'])) {
                $contentType = $response['ContentType'];
            }

            return new FileMetadata(
                sizeBytes: $sizeBytes,
                lastModified: $lastModified,
                eTag: $eTag,
                contentType: $contentType,
            );
        } catch (InvalidPathException $e) {
            throw $e;
        } catch (AwsException $e) {
            // 404 means object not found
            if ($e->getStatusCode() === 404) {
                throw FileNotFoundException::notFound($path);
            }
            // Other AWS errors
            throw ReaderAdapterException::fromThrowable($e);
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

}
