<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/image-profile
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-04-17
 */

declare(strict_types=1);

namespace Maatify\ImageProfileLegacy\Processor;

use Maatify\ImageProfileLegacy\Contract\ImageProcessorInterface;
use Maatify\ImageProfileLegacy\Contract\ImageVariantGeneratorInterface;
use Maatify\ImageProfileLegacy\DTO\GeneratedVariantCollectionDTO;
use Maatify\ImageProfileLegacy\DTO\GeneratedVariantDTO;
use Maatify\ImageProfileLegacy\DTO\VariantDefinitionCollectionDTO;
use Maatify\ImageProfileLegacy\DTO\VariantDefinitionDTO;
use Maatify\ImageProfileLegacy\Enum\ImageFormatEnum;
use Maatify\ImageProfileLegacy\Exception\ImageProfileException;

/**
 * Generates multiple named image variants from a single source image.
 *
 * Delegates each resize operation to an ImageProcessorInterface implementation.
 * Output files are written to $targetDirectory using the pattern:
 *
 *   {targetDirectory}/{variantName}.{extension}
 *
 * If the target directory does not exist or is not writable, an
 * ImageProfileException is thrown before any processing begins.
 *
 * Processing is sequential in v1. Failed variants throw immediately;
 * partial results are not returned.
 */
final class NativeImageVariantGenerator implements ImageVariantGeneratorInterface
{
    public function __construct(
        private readonly ImageProcessorInterface $processor,
    ) {
    }

    // -------------------------------------------------------------------------
    // ImageVariantGeneratorInterface
    // -------------------------------------------------------------------------

    public function generate(
        string $sourcePath,
        string $targetDirectory,
        VariantDefinitionCollectionDTO $variants,
    ): GeneratedVariantCollectionDTO {
        $this->assertDirectoryWritable($targetDirectory);

        $collection = GeneratedVariantCollectionDTO::empty();

        foreach ($variants as $definition) {
            $targetPath = $this->buildTargetPath($targetDirectory, $definition);
            $result     = $this->processor->resize($sourcePath, $targetPath, $definition->options);
            $collection = $collection->with(new GeneratedVariantDTO($definition->name, $result));
        }

        return $collection;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function assertDirectoryWritable(string $directory): void
    {
        if (! is_dir($directory)) {
            throw new class("Target directory does not exist: {$directory}") extends ImageProfileException {};
        }

        if (! is_writable($directory)) {
            throw new class("Target directory is not writable: {$directory}") extends ImageProfileException {};
        }
    }

    private function buildTargetPath(string $directory, VariantDefinitionDTO $definition): string
    {
        $ext = $definition->options->outputFormat instanceof ImageFormatEnum
            ? $definition->options->outputFormat->value
            : 'jpg'; // fallback; NativeImageProcessor::detectFormat() handles the actual format

        return rtrim($directory, '/\\') . DIRECTORY_SEPARATOR . $definition->name . '.' . $ext;
    }
}
