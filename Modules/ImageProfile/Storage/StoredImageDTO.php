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

use JsonSerializable;

/**
 * Immutable result of a successful image storage operation.
 *
 * Returned by {@see ImageStorageInterface::store()} after the file has been
 * persisted to the remote backend. Contains everything the application needs
 * to reference or serve the stored image.
 *
 * Fields:
 *   - `publicUrl`    The full public URL to serve the image (CDN or direct).
 *   - `remotePath`   The path within the bucket/container — used for future
 *                    delete or move operations.
 *   - `disk`         Backend identifier (e.g. "do-spaces", "s3", "local").
 *   - `sizeBytes`    Confirmed file size as stored on the backend.
 *   - `mimeType`     MIME type as stored (from the uploaded file metadata).
 */
final readonly class StoredImageDTO implements JsonSerializable
{
    public function __construct(
        public string $publicUrl,
        public string $remotePath,
        public string $disk,
        public int    $sizeBytes,
        public string $mimeType,
    ) {
    }

    /**
     * @return array{
     *     publicUrl: string,
     *     remotePath: string,
     *     disk: string,
     *     sizeBytes: int,
     *     mimeType: string
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'publicUrl'  => $this->publicUrl,
            'remotePath' => $this->remotePath,
            'disk'       => $this->disk,
            'sizeBytes'  => $this->sizeBytes,
            'mimeType'   => $this->mimeType,
        ];
    }
}
