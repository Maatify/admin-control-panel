<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/image-profile
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-04-16
 */

declare(strict_types=1);

namespace Maatify\ImageProfile\Entity;

use JsonSerializable;
use Maatify\ImageProfile\DTO\ImageProfileProcessingExtensionDTO;
use Maatify\ImageProfile\ValueObject\AllowedExtensionCollection;
use Maatify\ImageProfile\ValueObject\AllowedMimeTypeCollection;

/**
 * Immutable image profile entity — the core business abstraction.
 *
 * A profile is a reusable set of rules identified by a stable business
 * `code` (NOT by the database `id`). Consumers reference profiles by
 * code so that the meaning of a profile stays portable across
 * environments and storage backends.
 *
 * Rule semantics:
 *  - Any `null` min/max bound means the corresponding rule is disabled.
 *  - Empty extension / MIME collections mean the restriction is disabled.
 *  - `minAspectRatio` / `maxAspectRatio` are width÷height ratios (float).
 *    e.g. 16/9 ≈ 1.7778; 1/1 = 1.0; 9/16 = 0.5625.
 *  - `requiresTransparency = true` means only PNG and WebP are accepted.
 *  - `processing` is optional extension metadata for post-validation workflows;
 *    validator behavior does not depend on it.
 *
 * Backward compatibility: all Phase 9 fields have safe defaults so existing
 * construction code that does not supply them continues to work.
 */
final readonly class ImageProfileEntity implements JsonSerializable
{
    public function __construct(
        // -----------------------------------------------------------------------
        // Phase 1–3 fields
        // -----------------------------------------------------------------------
        public ?int                       $id,
        public string                     $code,
        public ?string                    $displayName,
        public ?int                       $minWidth,
        public ?int                       $minHeight,
        public ?int                       $maxWidth,
        public ?int                       $maxHeight,
        public ?int                       $maxSizeBytes,
        public AllowedExtensionCollection $allowedExtensions,
        public AllowedMimeTypeCollection  $allowedMimeTypes,
        public bool                       $isActive,
        public ?string                    $notes,

        // -----------------------------------------------------------------------
        // Phase 9 fields — all default to "no constraint / disabled"
        // -----------------------------------------------------------------------

        /** Minimum width÷height ratio (null = no constraint). */
        public ?float                          $minAspectRatio = null,

        /** Maximum width÷height ratio (null = no constraint). */
        public ?float                          $maxAspectRatio = null,

        /** If true the uploaded image MUST be PNG or WebP (alpha-capable). */
        public bool                            $requiresTransparency = false,

        /**
         * Optional extension metadata for post-validation processing.
         * Null means "no processing profile attached".
         */
        public ?ImageProfileProcessingExtensionDTO $processing = null,
    ) {
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function hasExtensionRestriction(): bool
    {
        return ! $this->allowedExtensions->isEmpty();
    }

    public function hasMimeTypeRestriction(): bool
    {
        return ! $this->allowedMimeTypes->isEmpty();
    }

    public function hasAspectRatioConstraint(): bool
    {
        return $this->minAspectRatio !== null || $this->maxAspectRatio !== null;
    }

    public function hasVariants(): bool
    {
        return $this->processing?->hasVariants() ?? false;
    }

    // -------------------------------------------------------------------------
    // JsonSerializable
    // -------------------------------------------------------------------------

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'id'                  => $this->id,
            'code'                => $this->code,
            'displayName'         => $this->displayName,
            'minWidth'            => $this->minWidth,
            'minHeight'           => $this->minHeight,
            'maxWidth'            => $this->maxWidth,
            'maxHeight'           => $this->maxHeight,
            'maxSizeBytes'        => $this->maxSizeBytes,
            'allowedExtensions'   => $this->allowedExtensions,
            'allowedMimeTypes'    => $this->allowedMimeTypes,
            'isActive'            => $this->isActive,
            'notes'               => $this->notes,
            'minAspectRatio'      => $this->minAspectRatio,
            'maxAspectRatio'      => $this->maxAspectRatio,
            'requiresTransparency' => $this->requiresTransparency,
            'processing'          => $this->processing,
        ];
    }
}
