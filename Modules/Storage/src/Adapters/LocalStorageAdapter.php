<?php

declare(strict_types=1);

namespace Maatify\Storage\Adapters;

use Maatify\Storage\Contracts\StorageAdapterInterface;
use Maatify\Storage\DTO\StoredFile;
use Maatify\Storage\Exception\AdapterException;
use Psr\Http\Message\UploadedFileInterface;

use function copy;
use function dirname;
use function file_exists;
use function ltrim;
use function unlink;

/**
 * Stores uploaded files on the local filesystem under a configured base path.
 *
 * The public URL is resolved by prepending {@see $baseUrl} to the relative path,
 * meaning the base URL can be changed without touching any stored database records.
 */
final class LocalStorageAdapter implements StorageAdapterInterface
{
    /** Absolute filesystem path to the storage root (e.g. /var/www/public/images). */
    private string $basePath;

    /** Base URL prefix used to resolve public URLs (e.g. /images or https://example.com/images). */
    private string $baseUrl;

    /**
     * @param string $basePath Absolute path to the local storage root directory.
     * @param string $baseUrl  Base URL prefix for resolving public URLs.
     */
    public function __construct(string $basePath, string $baseUrl = '/images')
    {
        $this->basePath = rtrim($basePath, '/');
        $this->baseUrl  = rtrim($baseUrl, '/');

        $this->ensureDirectoryExists($this->basePath);
    }

    /**
     * {@inheritDoc}
     *
     * Moves the uploaded file to {$basePath}/{$destinationPath} and returns
     * a StoredFile DTO containing both the relative path and the resolved URL.
     *
     * The URL is resolved using {@see url()} to ensure consistency with the adapter's
     * URL resolution logic.
     */
    public function store(UploadedFileInterface $file, string $destinationPath): StoredFile
    {
        $fullPath = $this->basePath . '/' . ltrim($destinationPath, '/');

        $this->ensureDirectoryExists(dirname($fullPath));

        $file->moveTo($fullPath);

        $path = ltrim($destinationPath, '/');
        return new StoredFile(
            path: $path,
            url: $this->url($path),
        );
    }

    /**
     * {@inheritDoc}
     *
     * Prepends {@see $baseUrl} to the relative path.
     * Example: "products/slug-abc123.jpg" → "/images/products/slug-abc123.jpg"
     */
    public function url(string $relativePath): string
    {
        return $this->baseUrl . '/' . ltrim($relativePath, '/');
    }

    /**
     * {@inheritDoc}
     *
     * Copies (does not move) the source file to the destination path.
     */
    public function storeFromPath(string $localPath, string $destinationPath): StoredFile
    {
        $fullPath = $this->basePath . '/' . ltrim($destinationPath, '/');

        $this->ensureDirectoryExists(dirname($fullPath));

        if (!@copy($localPath, $fullPath)) {
            throw AdapterException::failedToCopyFile($localPath, $fullPath);
        }

        $path = ltrim($destinationPath, '/');
        return new StoredFile(
            path: $path,
            url: $this->url($path),
        );
    }

    /**
     * {@inheritDoc}
     *
     * Local storage has no presigning concept — returns the same URL as {@see url()}.
     * The expiry parameter is ignored.
     */
    public function presign(string $relativePath, int $expiresInSeconds = 3600): string
    {
        return $this->url($relativePath);
    }

    /**
     * {@inheritDoc}
     *
     * Local storage has no CDN to bypass — returns the absolute filesystem path
     * so server-side processes can read the file directly without HTTP.
     * The expiry parameter is ignored.
     */
    public function downloadUrl(string $relativePath, int $expiresInSeconds = 3600): string
    {
        return $this->basePath . '/' . ltrim($relativePath, '/');
    }

    /**
     * {@inheritDoc}
     *
     * Silently ignores the file if it does not exist on disk.
     */
    public function delete(string $path): void
    {
        $fullPath = $this->basePath . '/' . ltrim($path, '/');

        if (file_exists($fullPath) && !unlink($fullPath)) {
            throw AdapterException::failedToDeleteFile($fullPath);
        }
    }

    /**
     * Creates the given directory recursively if it does not already exist.
     *
     * @param string $directory Absolute path to the directory to create.
     *
     * @throws AdapterException If the directory cannot be created.
     */
    private function ensureDirectoryExists(string $directory): void
    {
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw AdapterException::failedToCreateDirectory($directory);
        }
    }
}
