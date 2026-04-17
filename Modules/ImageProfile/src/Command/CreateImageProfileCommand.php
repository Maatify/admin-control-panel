<?php

declare(strict_types=1);

namespace Maatify\ImageProfile\Command;

/**
 * Carries all fields required to persist a new image profile.
 */
final readonly class CreateImageProfileCommand
{
    public function __construct(
        public string $code,
        public ?string $displayName = null,
        public ?int $minWidth = null,
        public ?int $minHeight = null,
        public ?int $maxWidth = null,
        public ?int $maxHeight = null,
        public ?int $maxSizeBytes = null,
        public ?string $allowedExtensions = null,
        public ?string $allowedMimeTypes = null,
        public bool $isActive = true,
        public ?string $notes = null,
        public ?string $minAspectRatio = null,
        public ?string $maxAspectRatio = null,
        public bool $requiresTransparency = false,
        public ?string $preferredFormat = null,
        public ?int $preferredQuality = null,
        public ?string $variants = null,
    ) {}
}
