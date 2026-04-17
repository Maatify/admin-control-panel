<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/image-profile
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-04-17
 */

declare(strict_types=1);

namespace Maatify\ImageProfile\Contract;

use Maatify\ImageProfile\DTO\GeneratedVariantCollectionDTO;
use Maatify\ImageProfile\DTO\VariantDefinitionCollectionDTO;
use Maatify\ImageProfile\Exception\ImageProfileException;

/**
 * Contract for generating multiple named image variants from a single source.
 *
 * Implementations iterate the provided VariantDefinitionCollectionDTO and
 * produce one output file per definition, writing each to $targetDirectory.
 * The resulting collection maps variant names to their ProcessedImageDTO results.
 *
 * This contract is intentionally separate from ImageProcessorInterface so that
 * variant generation can be composed differently (e.g. parallel, queued) in
 * future versions.
 */
interface ImageVariantGeneratorInterface
{
    /**
     * Generate all defined variants for $sourcePath and return their results.
     *
     * @param string                      $sourcePath      Absolute path to the source image.
     * @param string                      $targetDirectory Directory where variant files are written.
     *                                                      Must already exist and be writable.
     * @param VariantDefinitionCollectionDTO $variants      Ordered set of variant definitions.
     *
     * @throws ImageProfileException if any variant fails to generate.
     *
     * @return GeneratedVariantCollectionDTO Collection preserving insertion order of $variants.
     */
    public function generate(
        string $sourcePath,
        string $targetDirectory,
        VariantDefinitionCollectionDTO $variants,
    ): GeneratedVariantCollectionDTO;
}
