<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/image-profile
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-04-16
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\ImageProfile\Exception;

use Throwable;

/**
 * Thrown when a provider cannot resolve a profile by the given code.
 *
 * NOTE: The core validator does NOT throw this. It collects a
 * {@see \Maatify\ImageProfile\Enum\ValidationErrorCodeEnum::PROFILE_NOT_FOUND}
 * into the result. This exception is intended for callers that bypass the
 * validator and query the provider directly in contexts where a missing
 * profile is a programming or configuration error.
 */
final class ImageProfileNotFoundException extends ImageProfileException
{
    public static function forCode(string $code, ?Throwable $previous = null): self
    {
        return new self(
            sprintf('Image profile with code "%s" was not found.', $code),
            0,
            $previous,
        );
    }
}
