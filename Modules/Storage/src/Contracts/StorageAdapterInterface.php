<?php

declare(strict_types=1);

namespace Maatify\Storage\Contracts;

use Maatify\Storage\DTO\StoredFile;
use Psr\Http\Message\UploadedFileInterface;

interface StorageAdapterInterface
{
    /**
     * Store the uploaded file at the given destination path.
     *
     * Implementations MUST return a StoredFile DTO with both the relative path and resolved URL.
     * This allows callers maximum flexibility:
     * - Public uploads: use $url directly (full CDN URL)
     * - Private uploads: use $path for signed URL generation
     *
     * @param UploadedFileInterface $file            The uploaded file from the request.
     * @param string                $destinationPath Relative path including filename (e.g. "products/slug-abc123.jpg").
     *
     * @return StoredFile DTO containing path and url for the stored file.
     */
    public function store(UploadedFileInterface $file, string $destinationPath): StoredFile;

    /**
     * Resolve the full public URL from a stored relative path.
     *
     * This allows the base URL to change (e.g. CDN migration) without
     * affecting paths stored in the database.
     *
     * @param string $relativePath The relative path returned by {@see store()} (e.g. "products/slug-abc123.jpg").
     *
     * @return string Fully qualified public URL (e.g. "https://cdn.example.com/products/slug-abc123.jpg").
     */
    public function url(string $relativePath): string;

    /**
     * Store a local filesystem file at the given destination path.
     *
     * Unlike {@see store()}, this reads directly from a server-side file path
     * and does NOT delete the source file.
     *
     * Use case: moving fulfilled draft files (image_storage_type = 2) from
     * protected local draft storage to final private AR storage after payment.
     *
     * @param string $localPath       Absolute path to the source file on the server filesystem.
     * @param string $destinationPath Relative destination path including filename.
     *
     * @return StoredFile DTO containing path and url for the stored file.
     */
    public function storeFromPath(string $localPath, string $destinationPath): StoredFile;

    /**
     * Delete a stored file by its relative path.
     *
     * Implementations SHOULD silently ignore non-existent files.
     *
     * @param string $path The relative path of the file to delete (e.g. "products/slug-abc123.jpg").
     */
    public function delete(string $path): void;

    /**
     * Generate a time-limited URL for a stored file.
     *
     * For private remote storage (e.g. private DO Spaces): returns a presigned URL.
     * For public remote storage (CDN-backed): returns the permanent public URL via {@see url()}.
     * For local storage: returns the local URL via {@see url()}.
     *
     * @param string $relativePath    The relative path of the file.
     * @param int    $expiresInSeconds How long the URL should remain valid (ignored for public/local).
     *
     * @return string A URL the client can use to fetch the file.
     */
    public function presign(string $relativePath, int $expiresInSeconds = 3600): string;

    /**
     * Return a URL suitable for server-side downloads only (bypasses CDN).
     *
     * Unlike {@see presign()}, this method NEVER returns a CDN URL — it always
     * resolves through the origin storage endpoint (S3 presigned URL for DO Spaces,
     * absolute filesystem path for local storage). Use this in cron / background
     * jobs where CDN SSL configuration on the server may differ from the browser.
     *
     * @param string $relativePath     The relative path of the file.
     * @param int    $expiresInSeconds TTL for the presigned URL (ignored for local storage).
     *
     * @return string A URL (or absolute path) the server process can use to read the file.
     */
    public function downloadUrl(string $relativePath, int $expiresInSeconds = 3600): string;
}
