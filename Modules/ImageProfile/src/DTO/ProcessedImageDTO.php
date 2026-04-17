<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/image-profile
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-04-17
 */

declare(strict_types=1);

namespace Maatify\ImageProfile\DTO;

use JsonSerializable;

/**
 * Describes the result of a single processing operation (resize, optimize,
 * convertToWebp, etc.).
 *
 * @psalm-immutable
 */
final readonly class ProcessedImageDTO implements JsonSerializable
{
    /**
     * @param string $outputPath       Absolute path to the processed file on disk.
     * @param int    $width            Width of the processed image in pixels.
     * @param int    $height           Height of the processed image in pixels.
     * @param int    $sizeBytes        File size of the processed image in bytes.
     * @param string $mimeType         Detected MIME type of the output file.
     * @param string $format           File extension / format string (e.g. "jpg", "webp").
     * @param int    $processingTimeMs Wall-clock time spent on the operation in milliseconds.
     */
    public function __construct(
        public string $outputPath,
        public int $width,
        public int $height,
        public int $sizeBytes,
        public string $mimeType,
        public string $format,
        public int $processingTimeMs = 0,
    ) {
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** Returns the file name component of the output path. */
    public function fileName(): string
    {
        return basename($this->outputPath);
    }

    /** Returns the directory component of the output path. */
    public function directory(): string
    {
        return dirname($this->outputPath);
    }

    // -------------------------------------------------------------------------
    // JsonSerializable
    // -------------------------------------------------------------------------

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'outputPath'       => $this->outputPath,
            'width'            => $this->width,
            'height'           => $this->height,
            'sizeBytes'        => $this->sizeBytes,
            'mimeType'         => $this->mimeType,
            'format'           => $this->format,
            'processingTimeMs' => $this->processingTimeMs,
        ];
    }
}
