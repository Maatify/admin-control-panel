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
 * Thrown when the caller supplies an image input DTO that is structurally
 * invalid (e.g. empty original name, negative byte size, empty path).
 *
 * This represents MISUSE of the API — not a business validation failure.
 */
final class InvalidImageInputException extends ImageProfileException
{
    public static function emptyOriginalName(?Throwable $previous = null): self
    {
        return new self('Image input "originalName" must not be empty.', 0, $previous);
    }

    public static function emptyTemporaryPath(?Throwable $previous = null): self
    {
        return new self('Image input "temporaryPath" must not be empty.', 0, $previous);
    }

    public static function negativeSize(int $given, ?Throwable $previous = null): self
    {
        return new self(
            sprintf('Image input "sizeBytes" must be >= 0, given: %d.', $given),
            0,
            $previous,
        );
    }

    public static function invalidProcessingOption(string $field, string $reason, ?Throwable $previous = null): self
    {
        return new self(
            sprintf('Invalid processing option "%s": %s.', $field, $reason),
            0,
            $previous,
        );
    }

    public static function uploadError(
        string         $fileName,
        int            $errorCode,
        ?string        $message = null,
        ?Throwable     $previous = null,
    ): self {
        $detail = $message ?? sprintf('PHP upload error code: %d', $errorCode);

        return new self(
            sprintf('Upload failed for "%s": %s', $fileName, $detail),
            $errorCode,
            $previous,
        );
    }
}
