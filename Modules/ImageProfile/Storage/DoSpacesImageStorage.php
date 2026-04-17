<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/image-profile
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-04-17
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\ImageProfile\Storage;

use Aws\Exception\AwsException;
use Aws\S3\S3Client;
use Maatify\ImageProfile\Exception\ImageProfileException;

/**
 * DigitalOcean Spaces storage backend (S3-compatible API).
 *
 * Requires in your project composer.json:
 *   "aws/aws-sdk-php": "^3.0"
 *
 * Bootstrap example:
 * <code>
 * $s3 = new S3Client([
 *     'version'     => 'latest',
 *     'region'      => 'fra1',
 *     'endpoint'    => 'https://fra1.digitaloceanspaces.com',
 *     'credentials' => [
 *         'key'    => $_ENV['DO_SPACES_KEY'],
 *         'secret' => $_ENV['DO_SPACES_SECRET'],
 *     ],
 * ]);
 *
 * $storage = new DoSpacesImageStorage(
 *     client:     $s3,
 *     bucket:     $_ENV['DO_SPACES_BUCKET'],
 *     cdnBaseUrl: $_ENV['DO_SPACES_CDN_URL'], // e.g. https://cdn.example.com
 * );
 * </code>
 *
 * Storage flow:
 *   1. Validate the image with {@see \Maatify\ImageProfile\Validator\ImageProfileValidator}
 *   2. If valid → call `store()` with the tmp path and desired remote path
 *   3. Persist the returned `StoredImageDTO` data (public URL, remote path) to DB
 *
 * ACL:
 *   Default ACL is `public-read`. Pass a different ACL via `$acl` if needed.
 *   For private files, set `acl: 'private'` and generate pre-signed URLs separately.
 *
 * CDN:
 *   `$cdnBaseUrl` replaces the Spaces endpoint in the public URL so that
 *   assets are served through the CDN edge rather than the origin bucket.
 *   Leave empty to use the direct Spaces URL.
 */
final class DoSpacesImageStorage implements ImageStorageInterface
{
    private const DISK = 'do-spaces';

    public function __construct(
        private readonly S3Client $client,
        private readonly string   $bucket,
        private readonly string   $cdnBaseUrl = '',
    ) {
    }

    /**
     * Upload a local file to DigitalOcean Spaces.
     *
     * @param string $localPath   Absolute local path (e.g. validated tmp upload path)
     * @param string $remotePath  Destination key inside the bucket
     *                            (e.g. "images/categories/banner.webp")
     * @param string $acl         Canned ACL — default `public-read`
     *
     * @throws ImageProfileException on AWS / network failure
     */
    public function store(
        string $localPath,
        string $remotePath,
        string $acl = 'public-read',
    ): StoredImageDTO {
        $mimeType  = $this->detectMime($localPath);
        $sizeBytes = (int) filesize($localPath);

        try {
            $this->client->putObject([
                'Bucket'      => $this->bucket,
                'Key'         => $remotePath,
                'SourceFile'  => $localPath,
                'ACL'         => $acl,
                'ContentType' => $mimeType,
            ]);
        } catch (AwsException $e) {
            throw new class (
                sprintf(
                    'Failed to store "%s" to DO Spaces bucket "%s": %s',
                    $remotePath,
                    $this->bucket,
                    $e->getAwsErrorMessage() ?? $e->getMessage(),
                ),
                (int) $e->getCode(),
                $e,
            ) extends ImageProfileException {};
        }

        return new StoredImageDTO(
            publicUrl:  $this->buildPublicUrl($remotePath),
            remotePath: $remotePath,
            disk:       self::DISK,
            sizeBytes:  $sizeBytes,
            mimeType:   $mimeType,
        );
    }

    /**
     * Delete a previously stored file from DigitalOcean Spaces.
     *
     * @throws ImageProfileException on AWS / network failure
     */
    public function delete(string $remotePath): void
    {
        try {
            $this->client->deleteObject([
                'Bucket' => $this->bucket,
                'Key'    => $remotePath,
            ]);
        } catch (AwsException $e) {
            throw new class (
                sprintf(
                    'Failed to delete "%s" from DO Spaces bucket "%s": %s',
                    $remotePath,
                    $this->bucket,
                    $e->getAwsErrorMessage() ?? $e->getMessage(),
                ),
                (int) $e->getCode(),
                $e,
            ) extends ImageProfileException {};
        }
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function buildPublicUrl(string $remotePath): string
    {
        $base = rtrim($this->cdnBaseUrl !== ''
            ? $this->cdnBaseUrl
            : sprintf('https://%s.%s', $this->bucket, $this->resolveEndpointHost()),
            '/');

        return $base . '/' . ltrim($remotePath, '/');
    }

    private function resolveEndpointHost(): string
    {
        $endpoint = (string) $this->client->getEndpoint();
        // Strip protocol: "https://fra1.digitaloceanspaces.com" → "fra1.digitaloceanspaces.com"
        return preg_replace('#^https?://#', '', $endpoint) ?? 'digitaloceanspaces.com';
    }

    private function detectMime(string $localPath): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($localPath);

        return is_string($mime) ? $mime : 'application/octet-stream';
    }
}
