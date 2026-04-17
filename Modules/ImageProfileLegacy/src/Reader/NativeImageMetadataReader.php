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

namespace Maatify\ImageProfileLegacy\Reader;

use Maatify\ImageProfileLegacy\Contract\ImageMetadataReaderInterface;
use Maatify\ImageProfileLegacy\DTO\ImageFileInputDTO;
use Maatify\ImageProfileLegacy\DTO\ImageMetadataDTO;
use Maatify\ImageProfileLegacy\Exception\ImageMetadataReadException;
use Throwable;

/**
 * Native PHP metadata reader backed by {@see getimagesize()}.
 *
 * Responsibilities:
 *   - detect image dimensions
 *   - detect MIME type from file contents (not from client hint)
 *   - derive a canonical file extension
 *   - report actual file size (not the client-supplied size)
 */
final class NativeImageMetadataReader implements ImageMetadataReaderInterface
{
    public function read(ImageFileInputDTO $input): ImageMetadataDTO
    {
        $path = $input->temporaryPath;

        if (! is_file($path)) {
            throw ImageMetadataReadException::withReason(
                sprintf('Path "%s" is not a regular file.', $path),
            );
        }

        try {
            $info = @getimagesize($path);
        } catch (Throwable $e) {
            throw ImageMetadataReadException::forPath($path, $e);
        }

        if ($info === false) {
            throw ImageMetadataReadException::forPath($path);
        }

        // getimagesize() with a valid image always returns width, height, type, and mime.
        $width    = $info[0];
        $height   = $info[1];
        $typeCode = $info[2];
        $mimeType = strtolower(trim($info['mime']));

        if ($width === 0 || $height === 0 || $mimeType === '') {
            throw ImageMetadataReadException::forPath($path);
        }

        $extension = $this->extensionFromImageType($typeCode)
            ?? $this->extensionFromMime($mimeType)
            ?? $this->extensionFromOriginalName($input->originalName)
            ?? '';

        $actualSize = @filesize($path);
        if ($actualSize === false) {
            $actualSize = $input->sizeBytes;
        }

        return new ImageMetadataDTO(
            width:             $width,
            height:            $height,
            detectedMimeType:  $mimeType,
            detectedExtension: $extension,
            sizeBytes:         $actualSize,
        );
    }

    private function extensionFromImageType(int $typeCode): ?string
    {
        return match ($typeCode) {
            IMAGETYPE_GIF                                => 'gif',
            IMAGETYPE_JPEG                               => 'jpg',
            IMAGETYPE_PNG                                => 'png',
            IMAGETYPE_WEBP                               => 'webp',
            IMAGETYPE_BMP                                => 'bmp',
            IMAGETYPE_TIFF_II, IMAGETYPE_TIFF_MM         => 'tiff',
            IMAGETYPE_PSD                                => 'psd',
            IMAGETYPE_ICO                                => 'ico',
            IMAGETYPE_XBM                                => 'xbm',
            IMAGETYPE_WBMP                               => 'wbmp',
            IMAGETYPE_SWF                                => 'swf',
            default                                      => $this->extensionFromDefinedImageType($typeCode),
        };
    }

    /**
     * Handles image type constants that may not be defined on every PHP
     * build (e.g. AVIF was added in 8.1 but not always compiled in).
     */
    private function extensionFromDefinedImageType(int $typeCode): ?string
    {
        if (defined('IMAGETYPE_AVIF') && $typeCode === constant('IMAGETYPE_AVIF')) {
            return 'avif';
        }
        if (defined('IMAGETYPE_HEIC') && $typeCode === constant('IMAGETYPE_HEIC')) {
            return 'heic';
        }
        return null;
    }

    private function extensionFromMime(string $mime): ?string
    {
        return match ($mime) {
            'image/jpeg', 'image/jpg'       => 'jpg',
            'image/png'                     => 'png',
            'image/gif'                     => 'gif',
            'image/webp'                    => 'webp',
            'image/bmp', 'image/x-ms-bmp'   => 'bmp',
            'image/tiff'                    => 'tiff',
            'image/avif'                    => 'avif',
            'image/heic', 'image/heif'      => 'heic',
            'image/svg+xml'                 => 'svg',
            'image/vnd.microsoft.icon',
            'image/x-icon'                  => 'ico',
            default                         => null,
        };
    }

    private function extensionFromOriginalName(string $originalName): ?string
    {
        $ext = pathinfo($originalName, PATHINFO_EXTENSION);
        if (! is_string($ext) || $ext === '') {
            return null;
        }
        return strtolower($ext);
    }
}
