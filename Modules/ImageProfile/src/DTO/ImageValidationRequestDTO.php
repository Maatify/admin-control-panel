<?php

declare(strict_types=1);

namespace Maatify\ImageProfile\DTO;

/**
 * Metadata-only validation payload for site usage.
 */
final readonly class ImageValidationRequestDTO
{
    public function __construct(
        public int $width,
        public int $height,
        public int $sizeBytes,
        public ?string $extension = null,
        public ?string $mimeType = null,
        public bool $hasTransparency = false,
    ) {}
}
