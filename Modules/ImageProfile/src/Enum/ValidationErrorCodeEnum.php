<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/image-profile
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-04-16
 */

declare(strict_types=1);

namespace Maatify\ImageProfile\Enum;

/**
 * Stable, backward-compatible validation error codes.
 *
 * IMPORTANT: Once a case is released, its string VALUE must NEVER change.
 * The case NAME (left side) may only be renamed if all internal references
 * are updated in the same commit. String values are the public contract.
 *
 * Case naming follows PHP 8.1 PascalCase convention for backed enums.
 */
enum ValidationErrorCodeEnum: string
{
    // -------------------------------------------------------------------------
    // Infrastructure failures — short-circuit the validator immediately
    // -------------------------------------------------------------------------

    case ProfileNotFound   = 'profile_not_found';
    case ProfileInactive   = 'profile_inactive';
    case FileNotFound      = 'file_not_found';
    case FileNotReadable   = 'file_not_readable';
    case MetadataUnreadable = 'metadata_unreadable';

    // -------------------------------------------------------------------------
    // Rule failures — all collected before returning the result
    // -------------------------------------------------------------------------

    case MimeNotAllowed      = 'mime_not_allowed';
    case ExtensionNotAllowed = 'extension_not_allowed';
    case WidthTooSmall       = 'width_too_small';
    case HeightTooSmall      = 'height_too_small';
    case WidthTooLarge       = 'width_too_large';
    case HeightTooLarge      = 'height_too_large';
    case FileTooLarge        = 'file_too_large';

    // -------------------------------------------------------------------------
    // Phase 9 — aspect ratio and transparency rule failures
    // -------------------------------------------------------------------------

    /** width / height ratio is below the profile minimum. */
    case AspectRatioTooNarrow = 'aspect_ratio_too_narrow';

    /** width / height ratio is above the profile maximum. */
    case AspectRatioTooWide = 'aspect_ratio_too_wide';

    /** Profile requires alpha-channel format (PNG / WebP) but upload is JPEG or GIF. */
    case TransparencyRequired = 'transparency_required';
}
