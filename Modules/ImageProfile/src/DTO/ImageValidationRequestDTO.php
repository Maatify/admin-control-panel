<?php

declare(strict_types=1);

namespace Maatify\ImageProfile\DTO;

/**
 * Metadata-only validation payload for site usage.
 */
final class ImageValidationRequestDTO
{
    public function __construct(
        public readonly int $width,
        public readonly int $height,
        public readonly int $sizeBytes,
        public readonly ?string $extension = null,
        public readonly ?string $mimeType = null,
        public readonly bool $hasTransparency = false,
    ) {}
}
