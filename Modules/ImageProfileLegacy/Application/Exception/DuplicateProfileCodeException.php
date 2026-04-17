<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/image-profile
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-04-17
 * @see         https://www.maatify.dev Maatify.dev
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace ImageProfileLegacy\Application\Exception;

use Maatify\ImageProfileLegacy\Exception\ImageProfileException;
use Throwable;

/**
 * Thrown by {@see \ImageProfileLegacy\Application\Service\CreateImageProfileService}
 * when an admin attempts to create a profile whose `code` already exists.
 *
 * This is a business-rule violation, not an infrastructure error.
 * The `code` field is a stable business identifier and must be unique.
 */
final class DuplicateProfileCodeException extends ImageProfileException
{
    public static function forCode(string $code, ?Throwable $previous = null): self
    {
        return new self(
            sprintf('An image profile with code "%s" already exists.', $code),
            0,
            $previous,
        );
    }
}
