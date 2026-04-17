<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/image-profile
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-04-17
 * @see         https://www.maatify.dev Maatify.dev
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\ImageProfile\Storage;

use Maatify\ImageProfile\Exception\ImageProfileException;

/**
 * Write contract for image storage backends.
 *
 * Implementations:
 *   - {@see DoSpacesImageStorage}   DigitalOcean Spaces (S3-compatible)
 *
 * Future implementations (not in scope for v1):
 *   - S3ImageStorage      Amazon S3
 *   - LocalImageStorage   Local filesystem (for dev/testing)
 *   - GcsImageStorage     Google Cloud Storage
 *
 * Contract:
 *   - `store()` persists a local file to the remote backend and returns a
 *     typed {@see StoredImageDTO} with the confirmed remote path and public URL.
 *   - `delete()` removes a previously stored file using its remote path.
 *   - Both methods throw {@see ImageProfileException} on infrastructure failure.
 *
 * This interface is intentionally NOT in `src/` — it is a project-level concern.
 * The core validator knows nothing about storage; it only validates.
 */
interface ImageStorageInterface
{
    /**
     * Upload a local file to the storage backend.
     *
     * @param string $localPath   Absolute path to the local file (e.g. from tmp upload)
     * @param string $remotePath  Destination path inside the bucket/container
     *                            (e.g. "images/categories/banner.webp")
     *
     * @throws ImageProfileException on infrastructure failure (connection, permission, etc.)
     */
    public function store(string $localPath, string $remotePath): StoredImageDTO;

    /**
     * Delete a previously stored file by its remote path.
     *
     * @param string $remotePath  The same path returned in {@see StoredImageDTO::$remotePath}
     *
     * @throws ImageProfileException on infrastructure failure.
     */
    public function delete(string $remotePath): void;
}
