<?php

declare(strict_types=1);

namespace Maatify\ImageProfile\Command;

/**
 * Carries all fields required to persist a new image profile.
 */
final class CreateImageProfileCommand
{
    public function __construct(
        public readonly string $code,
        public readonly ?string $displayName = null,
        public readonly ?int $minWidth = null,
        public readonly ?int $minHeight = null,
        public readonly ?int $maxWidth = null,
        public readonly ?int $maxHeight = null,
        public readonly ?int $maxSizeBytes = null,
        public readonly ?string $allowedExtensions = null,
        public readonly ?string $allowedMimeTypes = null,
        public readonly bool $isActive = true,
        public readonly ?string $notes = null,
        public readonly ?string $minAspectRatio = null,
        public readonly ?string $maxAspectRatio = null,
        public readonly bool $requiresTransparency = false,
        public readonly ?string $preferredFormat = null,
        public readonly ?int $preferredQuality = null,
        public readonly ?string $variants = null,
    ) {}
}
