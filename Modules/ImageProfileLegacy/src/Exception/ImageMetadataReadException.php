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

use Throwable;

/**
 * Thrown by metadata readers when the underlying engine cannot produce a
 * usable {@see \Maatify\ImageProfileLegacy\DTO\ImageMetadataDTO} — e.g. the file
 * is not a recognizable image, is corrupt, or is unreadable by the engine.
 *
 * The core validator CATCHES this exception internally and converts it
 * into a
 * {@see \Maatify\ImageProfileLegacy\Enum\ValidationErrorCodeEnum::METADATA_UNREADABLE}
 * result entry.
 */
final class ImageMetadataReadException extends ImageProfileException
{
    public static function forPath(string $path, ?Throwable $previous = null): self
    {
        return new self(
            sprintf('Failed to read image metadata from path "%s".', $path),
            0,
            $previous,
        );
    }

    public static function withReason(string $reason, ?Throwable $previous = null): self
    {
        return new self($reason, 0, $previous);
    }
}
