<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/image-profile
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-04-16
 * @see         https://www.maatify.dev Maatify.dev
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\ImageProfileLegacy\Exception;

use RuntimeException;

/**
 * Root exception of the image-profile package.
 *
 * All package-specific exceptions MUST extend this class so that
 * consuming code can catch every image-profile error by a single type.
 *
 * Business validation failures (e.g. "image too small", "mime not allowed")
 * are NOT thrown as exceptions — they are returned via
 * {@see \Maatify\ImageProfileLegacy\DTO\ImageValidationResultDTO}.
 *
 * Exceptions are reserved for:
 *   - infrastructure errors
 *   - impossible/invalid states
 *   - misuse of the public API
 */
abstract class ImageProfileException extends RuntimeException
{
}
