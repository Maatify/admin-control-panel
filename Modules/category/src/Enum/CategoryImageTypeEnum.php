<?php

declare(strict_types=1);

namespace Maatify\Category\Enum;

use Maatify\Category\Exception\CategoryInvalidArgumentException;

/**
 * The supported image slots per category.
 *
 * Each slot can hold one image path per language, giving a matrix of:
 *   (category_id, image_type, language_id) → path
 *
 *  image         → default / general-purpose image
 *  mobile_image  → optimised for mobile renderers
 *  api_image     → served by the consumer API (e.g. mobile app)
 *  website_image → displayed on the public-facing website
 *
 * Extension point:
 *   The database stores image_type as a plain VARCHAR — no ENUM constraint.
 *   This enum is the application-layer registry of valid types.
 *   To add a new image slot, add a case here; no schema migration is needed.
 *   Do NOT call ::from() directly — use fromString() so that invalid values
 *   produce a CategoryInvalidArgumentException instead of a native \ValueError.
 */
enum CategoryImageTypeEnum: string
{
    case Image        = 'image';
    case MobileImage  = 'mobile_image';
    case ApiImage     = 'api_image';
    case WebsiteImage = 'website_image';

    /**
     * Safe alternative to ::from().
     *
     * Throws CategoryInvalidArgumentException (module exception family)
     * instead of the native \ValueError so callers stay inside the module's
     * exception contract.
     *
     * @throws CategoryInvalidArgumentException
     */
    public static function fromString(string $value): self
    {
        $case = self::tryFrom($value);

        if ($case === null) {
            $valid = implode(', ', array_column(self::cases(), 'value'));
            throw CategoryInvalidArgumentException::unexpectedType(
                sprintf('image_type (expected one of: %s)', $valid),
                $value,
            );
        }

        return $case;
    }
}

