<?php

declare(strict_types=1);

namespace Maatify\Storage\Adapters;

use Aws\S3\S3Client;
use Maatify\Storage\Contracts\StorageAdapterInterface;
use Maatify\Storage\DTO\StoredFile;
use Maatify\Storage\Exception\AdapterException;
use Maatify\Storage\Exception\FileUploadException;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Stores uploaded files on DigitalOcean Spaces via the S3-compatible API.
 *
 * The public URL is resolved by prepending {@see $cdnUrl} to the object key,
 * meaning the CDN URL can be changed without touching any stored database records.
 *
 * For private uploads, $cdnUrl can be null (signed URLs are used instead).
 */
final class DOSpacesStorageAdapter implements StorageAdapterInterface
{
    /**
     * @param S3Client $client S3-compatible client configured for DigitalOcean Spaces.
     * @param string   $bucket Target Spaces bucket name.
     * @param string|null $cdnUrl Base CDN URL used to resolve public URLs (null for private uploads).
     * @param string   $acl    Canned ACL applied to uploaded objects (default: public-read).
     */
    public function __construct(
        private readonly S3Client $client,
        private readonly string $bucket,
        private readonly ?string $cdnUrl,
        private readonly string $acl = 'public-read',
    ) {}

    /**
     * {@inheritDoc}
     *
     * Uploads the file to DigitalOcean Spaces under the given key and returns
     * a StoredFile DTO containing both the relative path and the resolved URL.
     *
     * The URL is resolved using {@see url()} to ensure consistency with the adapter's
     * URL resolution logic (handles CDN URLs for public uploads, paths for private).
     *
     * @throws FileUploadException If the file stream cannot be read.
     */
    public function store(UploadedFileInterface $file, string $destinationPath): StoredFile
    {
        $key    = ltrim($destinationPath, '/');
        $stream = $file->getStream()->detach();

        if (!is_resource($stream)) {
            throw FileUploadException::unreadableStream();
        }

        $this->client->putObject([
            'Bucket'      => $this->bucket,
            'Key'         => $key,
            'Body'        => $stream,
            'ACL'         => $this->acl,
            'ContentType' => $this->resolveMimeFromPath($key),
        ]);

        return new StoredFile(
            path: $key,
            url: $this->url($key),
        );
    }

    /**
     * {@inheritDoc}
     *
     * For public uploads: Prepends {@see $cdnUrl} to the object key.
     * Example: "products/slug-abc123.jpg" → "https://bucket.fra1.cdn.digitaloceanspaces.com/products/slug-abc123.jpg"
     *
     * For private uploads (null $cdnUrl): Returns empty string.
     * Private objects have no public URL; callers must generate a signed URL using the path.
     */
    public function url(string $relativePath): string
    {
        if ($this->cdnUrl === null || $this->cdnUrl === '') {
            return '';
        }

        return rtrim($this->cdnUrl, '/') . '/' . ltrim($relativePath, '/');
    }

    /**
     * {@inheritDoc}
     *
     * Opens the local file as a stream resource and uploads it to Spaces.
     * The source file is not deleted.
     */
    public function storeFromPath(string $localPath, string $destinationPath): StoredFile
    {
        $key    = ltrim($destinationPath, '/');
        $stream = @fopen($localPath, 'rb');

        if (!is_resource($stream)) {
            throw AdapterException::failedToReadLocalFile($localPath);
        }

        try {
            $this->client->putObject([
                'Bucket'      => $this->bucket,
                'Key'         => $key,
                'Body'        => $stream,
                'ACL'         => $this->acl,
                'ContentType' => $this->resolveMimeFromPath($key),
            ]);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        return new StoredFile(
            path: $key,
            url: $this->url($key),
        );
    }

    /**
     * {@inheritDoc}
     *
     * For private buckets (null $cdnUrl): generates a time-limited presigned GET URL
     * using the S3-compatible DigitalOcean Spaces presigning API.
     *
     * For public buckets: returns the permanent CDN URL (expiry is ignored).
     */
    public function presign(string $relativePath, int $expiresInSeconds = 3600): string
    {
        if ($this->cdnUrl !== null && $this->cdnUrl !== '') {
            return $this->url($relativePath);
        }

        $cmd     = $this->client->getCommand('GetObject', [
            'Bucket' => $this->bucket,
            'Key'    => ltrim($relativePath, '/'),
        ]);
        $request = $this->client->createPresignedRequest($cmd, "+{$expiresInSeconds} seconds");

        return (string) $request->getUri();
    }

    /**
     * {@inheritDoc}
     *
     * Always generates a presigned S3 endpoint URL regardless of whether a CDN
     * URL is configured. Intended for server-side background jobs (cron) where
     * the CDN custom domain may have SSL issues that do not affect browser clients.
     */
    public function downloadUrl(string $relativePath, int $expiresInSeconds = 3600): string
    {
        $cmd     = $this->client->getCommand('GetObject', [
            'Bucket' => $this->bucket,
            'Key'    => ltrim($relativePath, '/'),
        ]);
        $request = $this->client->createPresignedRequest($cmd, "+{$expiresInSeconds} seconds");

        return (string) $request->getUri();
    }

    /**
     * {@inheritDoc}
     *
     * Silently ignores the request if the object does not exist in the bucket.
     */
    public function delete(string $path): void
    {
        $this->client->deleteObject([
            'Bucket' => $this->bucket,
            'Key'    => ltrim($path, '/'),
        ]);
    }

    /**
     * Resolves the MIME type from the file extension in the given path.
     *
     * Falls back to "application/octet-stream" for unrecognized extensions.
     *
     * @param string $path File path or object key containing the extension.
     *
     * @return string MIME type string.
     */
    private function resolveMimeFromPath(string $path): string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png'         => 'image/png',
            'webp'        => 'image/webp',
            'gif'         => 'image/gif',
            'mp4'         => 'video/mp4',
            'webm'        => 'video/webm',
            'mov'         => 'video/quicktime',
            'avi'         => 'video/x-msvideo',
            'mkv'         => 'video/x-matroska',
            'mp3'         => 'audio/mpeg',
            'wav'         => 'audio/wav',
            'ogg'         => 'audio/ogg',
            default       => 'application/octet-stream',
        };
    }
}
