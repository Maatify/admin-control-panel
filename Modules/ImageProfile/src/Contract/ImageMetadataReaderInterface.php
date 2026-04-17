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

namespace Maatify\ImageProfile\Contract;

use Maatify\ImageProfile\DTO\ImageFileInputDTO;
use Maatify\ImageProfile\DTO\ImageMetadataDTO;
use Maatify\ImageProfile\Exception\ImageMetadataReadException;

/**
 * Abstraction over the engine that extracts metadata from an image file.
 *
 * The default Phase 1 implementation wraps native PHP image functions
 * ({@see getimagesize()}, finfo, etc.). Alternative implementations may
 * wrap Imagick, GD, or external services.
 */
interface ImageMetadataReaderInterface
{
    /**
     * @throws ImageMetadataReadException when the file cannot be read or
     *         is not a recognizable image.
     */
    public function read(ImageFileInputDTO $input): ImageMetadataDTO;
}
