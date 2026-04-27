<?php

declare(strict_types=1);

namespace Maatify\Category\DTO;

use JsonSerializable;
use Maatify\Category\Enum\CategoryImageTypeEnum;

/**
 * Typed collection of category images grouped by image type.
 *
 * Replaces the raw array<string, list<CategoryImageDTO>> return type.
 *
 * Every image-type slot is always present (even when empty) so callers
 * never need to check for key existence.
 *
 * Implements JsonSerializable so json_encode() produces:
 *   {"image":[...],"mobile_image":[...],"api_image":[...],"website_image":[...]}
 */
final class CategoryImagesCollection implements JsonSerializable
{
    /** @var array<string, list<CategoryImageDTO>> */
    private array $grouped;

    /**
     * @param array<string, list<CategoryImageDTO>> $grouped
     */
    public function __construct(array $grouped)
    {
        // Guarantee every known slot is present
        foreach (CategoryImageTypeEnum::cases() as $case) {
            if (!array_key_exists($case->value, $grouped)) {
                $grouped[$case->value] = [];
            }
        }

        $this->grouped = $grouped;
    }

    /**
     * Returns all images for a specific image type.
     *
     * @return list<CategoryImageDTO>
     */
    public function forType(CategoryImageTypeEnum $type): array
    {
        return $this->grouped[$type->value] ?? [];
    }

    /**
     * Returns the full grouped map.
     *
     * @return array<string, list<CategoryImageDTO>>
     */
    public function all(): array
    {
        return $this->grouped;
    }

    /**
     * @return array<string, list<CategoryImageDTO>>
     */
    public function jsonSerialize(): array
    {
        return $this->grouped;
    }
}

