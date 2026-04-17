<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/image-profile
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-04-17
 */

declare(strict_types=1);

namespace Maatify\ImageProfile\Enum;

/**
 * Supported output image formats.
 *
 * String values map directly to the canonical file extension used when
 * building output paths. They are stable once released.
 */
enum ImageFormatEnum: string
{
    case Jpeg = 'jpg';
    case Png  = 'png';
    case Webp = 'webp';
    case Gif  = 'gif';

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** Returns the canonical MIME type for this format. */
    public function mimeType(): string
    {
        return match ($this) {
            self::Jpeg => 'image/jpeg',
            self::Png  => 'image/png',
            self::Webp => 'image/webp',
            self::Gif  => 'image/gif',
        };
    }

    /**
     * Resolves an ImageFormatEnum from a file extension or MIME type string.
     * Returns null if the value is not recognised.
     */
    public static function fromString(string $value): ?self
    {
        $normalised = strtolower(trim($value, '. '));

        return match ($normalised) {
            'jpg', 'jpeg', 'image/jpeg' => self::Jpeg,
            'png',  'image/png'         => self::Png,
            'webp', 'image/webp'        => self::Webp,
            'gif',  'image/gif'         => self::Gif,
            default                     => null,
        };
    }
}
