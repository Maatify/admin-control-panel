<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/image-profile
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-04-17
 */

declare(strict_types=1);

namespace Maatify\ImageProfile\Application\DTO;

use JsonSerializable;
use Maatify\ImageProfile\DTO\VariantDefinitionCollectionDTO;
use Maatify\ImageProfile\Enum\ImageFormatEnum;
use Maatify\ImageProfile\ValueObject\AllowedExtensionCollection;
use Maatify\ImageProfile\ValueObject\AllowedMimeTypeCollection;

/**
 * Immutable input DTO for the "update image profile" use case.
 *
 * `code` is the stable identifier passed separately to the service.
 * It is NOT included here — the code is immutable once created.
 *
 * All Phase 9 fields default to "no constraint / disabled" for
 * backward compatibility with existing callers.
 */
final readonly class UpdateImageProfileRequest implements JsonSerializable
{
    public function __construct(
        public ?string                        $displayName,
        public ?int                           $minWidth,
        public ?int                           $minHeight,
        public ?int                           $maxWidth,
        public ?int                           $maxHeight,
        public ?int                           $maxSizeBytes,
        public AllowedExtensionCollection     $allowedExtensions,
        public AllowedMimeTypeCollection      $allowedMimeTypes,
        public ?string                        $notes = null,
        // Phase 9
        public ?float                         $minAspectRatio = null,
        public ?float                         $maxAspectRatio = null,
        public bool                           $requiresTransparency = false,
        public ?ImageFormatEnum               $preferredFormat = null,
        public ?int                           $preferredQuality = null,
        public VariantDefinitionCollectionDTO $variants = new VariantDefinitionCollectionDTO(),
    ) {
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'displayName'          => $this->displayName,
            'minWidth'             => $this->minWidth,
            'minHeight'            => $this->minHeight,
            'maxWidth'             => $this->maxWidth,
            'maxHeight'            => $this->maxHeight,
            'maxSizeBytes'         => $this->maxSizeBytes,
            'allowedExtensions'    => $this->allowedExtensions,
            'allowedMimeTypes'     => $this->allowedMimeTypes,
            'notes'                => $this->notes,
            'minAspectRatio'       => $this->minAspectRatio,
            'maxAspectRatio'       => $this->maxAspectRatio,
            'requiresTransparency' => $this->requiresTransparency,
            'preferredFormat'      => $this->preferredFormat?->value,
            'preferredQuality'     => $this->preferredQuality,
            'variants'             => $this->variants,
        ];
    }
}
