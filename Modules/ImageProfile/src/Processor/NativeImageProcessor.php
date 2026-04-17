<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/image-profile
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-04-17
 */

declare(strict_types=1);

namespace Maatify\ImageProfile\Processor;

use GdImage;
use Maatify\ImageProfile\Contract\ImageProcessorInterface;
use Maatify\ImageProfile\DTO\OptimizationOptionsDTO;
use Maatify\ImageProfile\DTO\ProcessedImageDTO;
use Maatify\ImageProfile\DTO\ResizeOptionsDTO;
use Maatify\ImageProfile\Enum\ImageFormatEnum;
use Maatify\ImageProfile\Enum\ResizeModeEnum;
use Maatify\ImageProfile\Exception\ImageMetadataReadException;
use Maatify\ImageProfile\Exception\ImageProfileException;
use Throwable;

/**
 * GD-based image processor.
 *
 * Requires: ext-gd (bundled with PHP, but must be enabled).
 *
 * All public methods:
 *  - throw ImageProfileException (or a subclass) on failure — never raw errors.
 *  - measure wall-clock processing time and include it in the result.
 *  - do NOT modify the source file.
 */
final class NativeImageProcessor implements ImageProcessorInterface
{
    // -------------------------------------------------------------------------
    // ImageProcessorInterface
    // -------------------------------------------------------------------------

    public function resize(
        string $sourcePath,
        string $targetPath,
        ResizeOptionsDTO $options,
    ): ProcessedImageDTO {
        $start = hrtime(true);

        $source = $this->loadImage($sourcePath);
        [$srcW, $srcH] = [$this->imageWidth($source), $this->imageHeight($source)];

        $canvas = match ($options->mode) {
            ResizeModeEnum::Fit     => $this->resizeFit($source, $srcW, $srcH, $options->width, $options->height),
            ResizeModeEnum::Fill    => $this->resizeFill($source, $srcW, $srcH, $options->width, $options->height),
            ResizeModeEnum::Stretch => $this->resizeStretch($source, $srcW, $srcH, $options->width, $options->height),
        };

        $format = $options->outputFormat ?? $this->detectFormat($sourcePath);
        $this->saveImage($canvas, $targetPath, $format, $options->quality);

        $elapsedMs = (int) round((hrtime(true) - $start) / 1_000_000);

        return $this->buildResult($targetPath, $canvas, $format, $elapsedMs);
    }

    public function optimize(
        string $sourcePath,
        string $targetPath,
        OptimizationOptionsDTO $options,
    ): ProcessedImageDTO {
        $start = hrtime(true);

        $source = $this->loadImage($sourcePath);
        $format = $options->targetFormat ?? $this->detectFormat($sourcePath);

        // Re-save through GD — this strips EXIF/XMP/IPTC data automatically
        // because GD never preserves metadata on output.
        $this->saveImage($source, $targetPath, $format, $options->quality);

        $elapsedMs = (int) round((hrtime(true) - $start) / 1_000_000);

        return $this->buildResult($targetPath, $source, $format, $elapsedMs);
    }

    public function convertToWebp(
        string $sourcePath,
        string $targetPath,
        int $quality = 80,
    ): ProcessedImageDTO {
        return $this->optimize(
            $sourcePath,
            $targetPath,
            OptimizationOptionsDTO::toWebp($quality),
        );
    }

    // -------------------------------------------------------------------------
    // Resize strategies
    // -------------------------------------------------------------------------

    /**
     * Scale uniformly to fit inside the bounding box — no crop, no distortion.
     * Output canvas is exactly $maxW × $maxH only if source exactly fills it;
     * otherwise it may be smaller in one dimension.
     */
    private function resizeFit(
        GdImage $source,
        int $srcW,
        int $srcH,
        int $maxW,
        int $maxH,
    ): GdImage {
        $ratio    = min($maxW / $srcW, $maxH / $srcH);
        $destW    = (int) round($srcW * $ratio);
        $destH    = (int) round($srcH * $ratio);

        $canvas = $this->createTrueColourCanvas($destW, $destH);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $destW, $destH, $srcW, $srcH);

        return $canvas;
    }

    /**
     * Scale to cover the bounding box, then centre-crop to exact dimensions.
     */
    private function resizeFill(
        GdImage $source,
        int $srcW,
        int $srcH,
        int $targetW,
        int $targetH,
    ): GdImage {
        $ratio    = max($targetW / $srcW, $targetH / $srcH);
        $scaledW  = (int) round($srcW * $ratio);
        $scaledH  = (int) round($srcH * $ratio);

        // Offset for centre-crop
        $offsetX  = (int) round(($scaledW - $targetW) / 2);
        $offsetY  = (int) round(($scaledH - $targetH) / 2);

        $canvas = $this->createTrueColourCanvas($targetW, $targetH);
        imagecopyresampled(
            $canvas, $source,
            0, 0,
            (int) round(-$offsetX / $ratio), (int) round(-$offsetY / $ratio),
            $targetW, $targetH,
            (int) round($targetW / $ratio), (int) round($targetH / $ratio),
        );

        return $canvas;
    }

    /**
     * Force exact dimensions regardless of aspect ratio (may distort).
     */
    private function resizeStretch(
        GdImage $source,
        int $srcW,
        int $srcH,
        int $targetW,
        int $targetH,
    ): GdImage {
        $canvas = $this->createTrueColourCanvas($targetW, $targetH);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetW, $targetH, $srcW, $srcH);

        return $canvas;
    }

    // -------------------------------------------------------------------------
    // GD helpers
    // -------------------------------------------------------------------------

    private function loadImage(string $path): GdImage
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new class("Image file not readable: {$path}") extends ImageProfileException {};
        }

        $info = @getimagesize($path);
        if ($info === false) {
            throw ImageMetadataReadException::forPath($path);
        }

        $image = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG  => @imagecreatefrompng($path),
            IMAGETYPE_WEBP => @imagecreatefromwebp($path),
            IMAGETYPE_GIF  => @imagecreatefromgif($path),
            default        => false,
        };

        if ($image === false) {
            throw new class("Failed to load image from: {$path}") extends ImageProfileException {};
        }

        // Preserve alpha channel for PNG / WebP
        if (in_array($info[2], [IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            imagealphablending($image, false);
            imagesavealpha($image, true);
        }

        return $image;
    }

    private function createTrueColourCanvas(int $width, int $height): GdImage
    {
        $canvas = imagecreatetruecolor($width, $height);

        if ($canvas === false) {
            throw new class("Failed to allocate GD canvas {$width}×{$height}") extends ImageProfileException {};
        }

        // Enable alpha channel support on the canvas
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);

        // Transparent background
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        if ($transparent !== false) {
            imagefill($canvas, 0, 0, $transparent);
        }

        return $canvas;
    }

    private function saveImage(GdImage $image, string $path, ImageFormatEnum $format, int $quality): void
    {
        $result = match ($format) {
            ImageFormatEnum::Jpeg => imagejpeg($image, $path, $quality),
            ImageFormatEnum::Png  => imagepng($image, $path, (int) round((100 - $quality) / 10)),
            ImageFormatEnum::Webp => imagewebp($image, $path, $quality),
            ImageFormatEnum::Gif  => imagegif($image, $path),
        };

        if ($result === false) {
            throw new class("Failed to write processed image to: {$path}") extends ImageProfileException {};
        }
    }

    private function detectFormat(string $path): ImageFormatEnum
    {
        $info = @getimagesize($path);

        if ($info === false) {
            throw ImageMetadataReadException::forPath($path);
        }

        $mime   = (string) $info['mime'];
        $format = ImageFormatEnum::fromString($mime);

        if ($format === null) {
            throw new class("Unrecognised image format for: {$path}") extends ImageProfileException {};
        }

        return $format;
    }

    private function imageWidth(GdImage $image): int
    {
        return imagesx($image);
    }

    private function imageHeight(GdImage $image): int
    {
        return imagesy($image);
    }

    /**
     * Builds a ProcessedImageDTO from a written output file + GD resource.
     *
     * @throws ImageProfileException if the output file cannot be stat'd.
     */
    private function buildResult(string $path, GdImage $image, ImageFormatEnum $format, int $elapsedMs): ProcessedImageDTO
    {
        $size = @filesize($path);

        if ($size === false) {
            throw new class("Cannot stat output file: {$path}") extends ImageProfileException {};
        }

        return new ProcessedImageDTO(
            outputPath:       $path,
            width:            $this->imageWidth($image),
            height:           $this->imageHeight($image),
            sizeBytes:        $size,
            mimeType:         $format->mimeType(),
            format:           $format->value,
            processingTimeMs: $elapsedMs,
        );
    }
}
