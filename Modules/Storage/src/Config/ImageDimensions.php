<?php

declare(strict_types=1);

namespace Maatify\Storage\Config;

/**
 * Image dimension constraints.
 *
 * Used by ImageDimensionsValidator to enforce minimum and maximum
 * width and height constraints on uploaded images.
 */
final readonly class ImageDimensions
{
    public function __construct(
        public int $minWidth,
        public int $minHeight,
        public int $maxWidth,
        public int $maxHeight,
    ) {}
}
