<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/image-profile
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-04-16
 * @see         https://www.maatify.dev Maatify.dev
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\ImageProfileLegacy\DTO;

use JsonSerializable;

/**
 * Trustworthy image metadata produced by an
 * {@see \Maatify\ImageProfileLegacy\Contract\ImageMetadataReaderInterface}.
 *
 * Fields on this DTO represent values DETECTED from the file itself,
 * not values advertised by the client (see {@see ImageFileInputDTO}
 * for the client-provided counterparts).
 */
final readonly class ImageMetadataDTO implements JsonSerializable
{
    public function __construct(
        public int    $width,
        public int    $height,
        public string $detectedMimeType,
        public string $detectedExtension,
        public int    $sizeBytes,
    ) {
    }

    /**
     * @return array{
     *     width: int,
     *     height: int,
     *     detectedMimeType: string,
     *     detectedExtension: string,
     *     sizeBytes: int
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'width'             => $this->width,
            'height'            => $this->height,
            'detectedMimeType'  => $this->detectedMimeType,
            'detectedExtension' => $this->detectedExtension,
            'sizeBytes'         => $this->sizeBytes,
        ];
    }
}
