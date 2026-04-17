<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/image-profile
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-04-17
 */

declare(strict_types=1);

namespace Maatify\ImageProfile\Contract;

use Maatify\ImageProfile\DTO\OptimizationOptionsDTO;
use Maatify\ImageProfile\DTO\ProcessedImageDTO;
use Maatify\ImageProfile\DTO\ResizeOptionsDTO;
use Maatify\ImageProfile\Exception\ImageProfileException;

/**
 * Contract for image processing operations.
 *
 * EXTENSION SCOPE NOTICE (post-v1 core):
 * This contract is intentionally optional and is NOT part of the stable
 * validation-first public path of the library. Consumers that only need
 * profile-based validation should depend on ImageProfileValidationService
 * and ignore processing APIs entirely.
 *
 * Implementations must remain completely separate from validation logic.
 * A processor reads a source file and writes an output file — it does not
 * know about image profiles, upload rules, or validation results.
 *
 * All methods throw ImageProfileException (or a subclass) on failure.
 * They never return null.
 */
interface ImageProcessorInterface
{
    /**
     * Resize a source image according to $options and write the result to
     * $targetPath. The parent directory of $targetPath must already exist.
     *
     * @param string           $sourcePath  Absolute path to the source file.
     * @param string           $targetPath  Absolute path where the output is written.
     * @param ResizeOptionsDTO $options     Resize parameters.
     *
     * @throws ImageProfileException on read, processing, or write failure.
     */
    public function resize(
        string $sourcePath,
        string $targetPath,
        ResizeOptionsDTO $options,
    ): ProcessedImageDTO;

    /**
     * Re-encode a source image with the given optimisation settings and write
     * the result to $targetPath.
     *
     * @param string                $sourcePath Absolute path to the source file.
     * @param string                $targetPath Absolute path where the output is written.
     * @param OptimizationOptionsDTO $options    Optimisation parameters.
     *
     * @throws ImageProfileException on read, processing, or write failure.
     */
    public function optimize(
        string $sourcePath,
        string $targetPath,
        OptimizationOptionsDTO $options,
    ): ProcessedImageDTO;

    /**
     * Convert any supported image format to WebP and write to $targetPath.
     * Equivalent to optimize() with OptimizationOptionsDTO::toWebp().
     *
     * @param string $sourcePath Absolute path to the source file.
     * @param string $targetPath Absolute path where the WebP output is written.
     * @param int    $quality    WebP quality (1–100).
     *
     * @throws ImageProfileException on read, processing, or write failure.
     */
    public function convertToWebp(
        string $sourcePath,
        string $targetPath,
        int $quality = 80,
    ): ProcessedImageDTO;
}
