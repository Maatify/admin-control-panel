<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/image-profile
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-04-17
 */

declare(strict_types=1);

namespace Maatify\ImageProfile\DTO;

use JsonSerializable;

/**
 * Defines a single named image variant (e.g. "thumbnail", "medium", "large").
 *
 * @psalm-immutable
 */
final readonly class VariantDefinitionDTO implements JsonSerializable
{
    /**
     * @param string           $name    Stable identifier for this variant
     *                                   (e.g. "thumbnail", "preview").
     * @param ResizeOptionsDTO $options Resize parameters for this variant.
     */
    public function __construct(
        public string $name,
        public ResizeOptionsDTO $options,
    ) {
    }

    // -------------------------------------------------------------------------
    // Named constructors
    // -------------------------------------------------------------------------

    public static function thumbnail(int $width = 150, int $height = 150, int $quality = 80): self
    {
        return new self('thumbnail', ResizeOptionsDTO::webpThumbnail($width, $height, $quality));
    }

    public static function medium(int $width = 800, int $height = 600, int $quality = 85): self
    {
        return new self('medium', ResizeOptionsDTO::fit($width, $height, $quality));
    }

    public static function large(int $width = 1920, int $height = 1080, int $quality = 90): self
    {
        return new self('large', ResizeOptionsDTO::fit($width, $height, $quality));
    }

    // -------------------------------------------------------------------------
    // JsonSerializable
    // -------------------------------------------------------------------------

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'name'    => $this->name,
            'options' => $this->options->jsonSerialize(),
        ];
    }
}
