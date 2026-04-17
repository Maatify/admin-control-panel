<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/image-profile
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-04-16
 */

declare(strict_types=1);

namespace Maatify\ImageProfile\Tests\Fixtures;

use Maatify\ImageProfile\DTO\ResizeOptionsDTO;
use Maatify\ImageProfile\DTO\ImageProfileProcessingExtensionDTO;
use Maatify\ImageProfile\DTO\VariantDefinitionCollectionDTO;
use Maatify\ImageProfile\DTO\VariantDefinitionDTO;
use Maatify\ImageProfile\Entity\ImageProfileEntity;
use Maatify\ImageProfile\Enum\ImageFormatEnum;
use Maatify\ImageProfile\ValueObject\AllowedExtensionCollection;
use Maatify\ImageProfile\ValueObject\AllowedMimeTypeCollection;

/**
 * Factory for test ImageProfile entities.
 *
 * All methods return fully constructed, immutable ImageProfile instances.
 * Use these in tests instead of constructing profiles inline — this
 * centralizes fixture data and makes test intentions explicit.
 */
final class ImageProfileFixtureFactory
{
    /**
     * A standard active profile accepting JPEG, PNG, and WebP images
     * with generous size bounds. Used as the "happy path" fixture.
     */
    public static function standard(): ImageProfileEntity
    {
        return new ImageProfileEntity(
            id:                1,
            code:              'standard_profile',
            displayName:       'Standard Profile',
            minWidth:          100,
            minHeight:         100,
            maxWidth:          4000,
            maxHeight:         4000,
            maxSizeBytes:      5_242_880, // 5 MB
            allowedExtensions: new AllowedExtensionCollection('jpg', 'jpeg', 'png', 'webp'),
            allowedMimeTypes:  new AllowedMimeTypeCollection('image/jpeg', 'image/png', 'image/webp'),
            isActive:          true,
            notes:             null,
        );
    }

    /**
     * Active profile with no extension or MIME restrictions.
     * Useful for testing size/dimension rules in isolation.
     */
    public static function noTypeRestriction(): ImageProfileEntity
    {
        return new ImageProfileEntity(
            id:                2,
            code:              'no_type_restriction',
            displayName:       'No Type Restriction',
            minWidth:          50,
            minHeight:         50,
            maxWidth:          2000,
            maxHeight:         2000,
            maxSizeBytes:      2_097_152, // 2 MB
            allowedExtensions: new AllowedExtensionCollection(),
            allowedMimeTypes:  new AllowedMimeTypeCollection(),
            isActive:          true,
            notes:             null,
        );
    }

    /**
     * Active profile with no restrictions at all.
     * Any image passes every rule.
     */
    public static function unrestricted(): ImageProfileEntity
    {
        return new ImageProfileEntity(
            id:                3,
            code:              'unrestricted',
            displayName:       'Unrestricted',
            minWidth:          null,
            minHeight:         null,
            maxWidth:          null,
            maxHeight:         null,
            maxSizeBytes:      null,
            allowedExtensions: new AllowedExtensionCollection(),
            allowedMimeTypes:  new AllowedMimeTypeCollection(),
            isActive:          true,
            notes:             null,
        );
    }

    /**
     * Inactive profile — validator must reject it before checking rules.
     */
    public static function inactive(): ImageProfileEntity
    {
        return new ImageProfileEntity(
            id:                4,
            code:              'inactive_profile',
            displayName:       'Inactive Profile',
            minWidth:          null,
            minHeight:         null,
            maxWidth:          null,
            maxHeight:         null,
            maxSizeBytes:      null,
            allowedExtensions: new AllowedExtensionCollection(),
            allowedMimeTypes:  new AllowedMimeTypeCollection(),
            isActive:          false,
            notes:             'Disabled for testing',
        );
    }

    /**
     * Profile with very strict minimum dimensions.
     * Used to trigger width_too_small / height_too_small errors.
     */
    public static function strictMinDimensions(): ImageProfileEntity
    {
        return new ImageProfileEntity(
            id:                5,
            code:              'strict_min_dimensions',
            displayName:       'Strict Min Dimensions',
            minWidth:          1920,
            minHeight:         1080,
            maxWidth:          null,
            maxHeight:         null,
            maxSizeBytes:      null,
            allowedExtensions: new AllowedExtensionCollection(),
            allowedMimeTypes:  new AllowedMimeTypeCollection(),
            isActive:          true,
            notes:             null,
        );
    }

    /**
     * Profile with very strict maximum dimensions.
     * Used to trigger width_too_large / height_too_large errors.
     */
    public static function strictMaxDimensions(): ImageProfileEntity
    {
        return new ImageProfileEntity(
            id:                6,
            code:              'strict_max_dimensions',
            displayName:       'Strict Max Dimensions',
            minWidth:          null,
            minHeight:         null,
            maxWidth:          10,
            maxHeight:         10,
            maxSizeBytes:      null,
            allowedExtensions: new AllowedExtensionCollection(),
            allowedMimeTypes:  new AllowedMimeTypeCollection(),
            isActive:          true,
            notes:             null,
        );
    }

    /**
     * Profile with a very low file size limit.
     * Used to trigger file_too_large errors.
     */
    public static function strictMaxSize(): ImageProfileEntity
    {
        return new ImageProfileEntity(
            id:                7,
            code:              'strict_max_size',
            displayName:       'Strict Max Size',
            minWidth:          null,
            minHeight:         null,
            maxWidth:          null,
            maxHeight:         null,
            maxSizeBytes:      1, // 1 byte — anything real will fail
            allowedExtensions: new AllowedExtensionCollection(),
            allowedMimeTypes:  new AllowedMimeTypeCollection(),
            isActive:          true,
            notes:             null,
        );
    }

    /**
     * Profile that only accepts WebP.
     * Used to trigger mime_not_allowed / extension_not_allowed on JPEG/PNG.
     */
    public static function webpOnly(): ImageProfileEntity
    {
        return new ImageProfileEntity(
            id:                8,
            code:              'webp_only',
            displayName:       'WebP Only',
            minWidth:          null,
            minHeight:         null,
            maxWidth:          null,
            maxHeight:         null,
            maxSizeBytes:      null,
            allowedExtensions: new AllowedExtensionCollection('webp'),
            allowedMimeTypes:  new AllowedMimeTypeCollection('image/webp'),
            isActive:          true,
            notes:             null,
        );
    }

    // =========================================================================
    // Phase 9 fixtures
    // =========================================================================

    /**
     * Profile that enforces a landscape aspect ratio (minAspectRatio = 16/9 ≈ 1.7778).
     * Portrait or square images will trigger aspect_ratio_too_narrow.
     */
    public static function landscapeOnly(): ImageProfileEntity
    {
        return new ImageProfileEntity(
            id:                9,
            code:              'landscape_only',
            displayName:       'Landscape Only (16:9+)',
            minWidth:          null,
            minHeight:         null,
            maxWidth:          null,
            maxHeight:         null,
            maxSizeBytes:      null,
            allowedExtensions: new AllowedExtensionCollection(),
            allowedMimeTypes:  new AllowedMimeTypeCollection(),
            isActive:          true,
            notes:             null,
            minAspectRatio:    16 / 9, // ≈ 1.7778
        );
    }

    /**
     * Profile that enforces a square-or-portrait aspect ratio (maxAspectRatio = 1.0).
     * Landscape images will trigger aspect_ratio_too_wide.
     */
    public static function portraitOrSquare(): ImageProfileEntity
    {
        return new ImageProfileEntity(
            id:                10,
            code:              'portrait_or_square',
            displayName:       'Portrait or Square (≤ 1:1)',
            minWidth:          null,
            minHeight:         null,
            maxWidth:          null,
            maxHeight:         null,
            maxSizeBytes:      null,
            allowedExtensions: new AllowedExtensionCollection(),
            allowedMimeTypes:  new AllowedMimeTypeCollection(),
            isActive:          true,
            notes:             null,
            maxAspectRatio:    1.0,
        );
    }

    /**
     * Profile that requires transparency-capable format (PNG or WebP).
     * JPEG uploads trigger transparency_required.
     */
    public static function requiresTransparency(): ImageProfileEntity
    {
        return new ImageProfileEntity(
            id:                11,
            code:              'requires_transparency',
            displayName:       'Transparency Required',
            minWidth:          null,
            minHeight:         null,
            maxWidth:          null,
            maxHeight:         null,
            maxSizeBytes:      null,
            allowedExtensions: new AllowedExtensionCollection(),
            allowedMimeTypes:  new AllowedMimeTypeCollection(),
            isActive:          true,
            notes:             null,
            requiresTransparency: true,
        );
    }

    /**
     * Profile with two named variants (thumbnail WebP + medium JPEG).
     * Used to verify that the variants collection is attached to the profile.
     */
    public static function withVariants(): ImageProfileEntity
    {
        $variants = new VariantDefinitionCollectionDTO(
            new VariantDefinitionDTO('thumbnail', ResizeOptionsDTO::webpThumbnail(150, 150)),
            new VariantDefinitionDTO('medium', ResizeOptionsDTO::fit(800, 600, 85)),
        );

        return new ImageProfileEntity(
            id:                12,
            code:              'with_variants',
            displayName:       'Profile With Variants',
            minWidth:          null,
            minHeight:         null,
            maxWidth:          null,
            maxHeight:         null,
            maxSizeBytes:      null,
            allowedExtensions: new AllowedExtensionCollection(),
            allowedMimeTypes:  new AllowedMimeTypeCollection(),
            isActive:          true,
            notes:             null,
            processing: new ImageProfileProcessingExtensionDTO(
                preferredFormat: ImageFormatEnum::Webp,
                preferredQuality: 85,
                variants: $variants,
            ),
        );
    }
}
