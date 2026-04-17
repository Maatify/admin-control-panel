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
 * The result of generating one named image variant.
 *
 * @psalm-immutable
 */
final readonly class GeneratedVariantDTO implements JsonSerializable
{
    /**
     * @param string           $name   Matches the VariantDefinitionDTO name.
     * @param ProcessedImageDTO $result Output details for this variant.
     */
    public function __construct(
        public string $name,
        public ProcessedImageDTO $result,
    ) {
    }

    // -------------------------------------------------------------------------
    // JsonSerializable
    // -------------------------------------------------------------------------

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'name'   => $this->name,
            'result' => $this->result->jsonSerialize(),
        ];
    }
}
