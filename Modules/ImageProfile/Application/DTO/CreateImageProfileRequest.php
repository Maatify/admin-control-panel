<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/image-profile
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-04-17
 */

declare(strict_types=1);

namespace Maatify\ImageProfile\Application\DTO;

use JsonSerializable;
use Maatify\ImageProfile\DTO\ImageProfileProcessingExtensionDTO;
use Maatify\ImageProfile\ValueObject\AllowedExtensionCollection;
use Maatify\ImageProfile\ValueObject\AllowedMimeTypeCollection;

/**
 * Immutable input DTO for the "create image profile" use case.
 *
 * Validation-first core fields are canonical.
 * Optional processing metadata, if needed, is carried via $processing.
 */
final readonly class CreateImageProfileRequest implements JsonSerializable
{
    public function __construct(
        public string                        $code,
        public ?string                       $displayName,
        public ?int                          $minWidth,
        public ?int                          $minHeight,
        public ?int                          $maxWidth,
        public ?int                          $maxHeight,
        public ?int                          $maxSizeBytes,
        public AllowedExtensionCollection    $allowedExtensions,
        public AllowedMimeTypeCollection     $allowedMimeTypes,
        public bool                          $isActive = true,
        public ?string                       $notes = null,
        public ?float                        $minAspectRatio = null,
        public ?float                        $maxAspectRatio = null,
        public bool                          $requiresTransparency = false,
        public ?ImageProfileProcessingExtensionDTO $processing = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'code'                 => $this->code,
            'displayName'          => $this->displayName,
            'minWidth'             => $this->minWidth,
            'minHeight'            => $this->minHeight,
            'maxWidth'             => $this->maxWidth,
            'maxHeight'            => $this->maxHeight,
            'maxSizeBytes'         => $this->maxSizeBytes,
            'allowedExtensions'    => $this->allowedExtensions,
            'allowedMimeTypes'     => $this->allowedMimeTypes,
            'isActive'             => $this->isActive,
            'notes'                => $this->notes,
            'minAspectRatio'       => $this->minAspectRatio,
            'maxAspectRatio'       => $this->maxAspectRatio,
            'requiresTransparency' => $this->requiresTransparency,
            'processing'           => $this->processing,
        ];
    }
}
