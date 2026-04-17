<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/image-profile
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-04-16
 */

declare(strict_types=1);

namespace Maatify\ImageProfileLegacy\DTO;

use JsonSerializable;
use Maatify\ImageProfileLegacy\Enum\ValidationErrorCodeEnum;

/**
 * Single validation failure reported by the validator.
 *
 * `expected` and `actual` are optional context fields stored as strings
 * so that the error envelope stays serialization-stable regardless of
 * the numeric/string nature of the underlying rule.
 */
final readonly class ImageValidationErrorDTO implements JsonSerializable
{
    public function __construct(
        public ValidationErrorCodeEnum $code,
        public string                  $message,
        public ?string                 $expected = null,
        public ?string                 $actual   = null,
    ) {
    }

    // -------------------------------------------------------------------------
    // Infrastructure failure constructors
    // -------------------------------------------------------------------------

    public static function profileNotFound(string $profileCode): self
    {
        return new self(
            ValidationErrorCodeEnum::ProfileNotFound,
            sprintf('Image profile "%s" was not found.', $profileCode),
        );
    }

    public static function profileInactive(string $profileCode): self
    {
        return new self(
            ValidationErrorCodeEnum::ProfileInactive,
            sprintf('Image profile "%s" is not active.', $profileCode),
        );
    }

    public static function fileNotFound(string $path): self
    {
        return new self(
            ValidationErrorCodeEnum::FileNotFound,
            sprintf('File does not exist: "%s".', $path),
        );
    }

    public static function fileNotReadable(string $path): self
    {
        return new self(
            ValidationErrorCodeEnum::FileNotReadable,
            sprintf('File is not readable: "%s".', $path),
        );
    }

    public static function metadataUnreadable(string $reason): self
    {
        return new self(
            ValidationErrorCodeEnum::MetadataUnreadable,
            $reason,
        );
    }

    // -------------------------------------------------------------------------
    // Rule failure constructors (phases 1–3)
    // -------------------------------------------------------------------------

    public static function mimeNotAllowed(string $detectedMime, string $allowedList): self
    {
        return new self(
            ValidationErrorCodeEnum::MimeNotAllowed,
            sprintf('MIME type "%s" is not allowed by this profile.', $detectedMime),
            expected: $allowedList,
            actual:   $detectedMime,
        );
    }

    public static function extensionNotAllowed(string $detectedExt, string $allowedList): self
    {
        return new self(
            ValidationErrorCodeEnum::ExtensionNotAllowed,
            sprintf('File extension "%s" is not allowed by this profile.', $detectedExt),
            expected: $allowedList,
            actual:   $detectedExt,
        );
    }

    public static function widthTooSmall(int $min, int $actual): self
    {
        return new self(
            ValidationErrorCodeEnum::WidthTooSmall,
            sprintf('Image width %dpx is smaller than required %dpx.', $actual, $min),
            expected: (string) $min,
            actual:   (string) $actual,
        );
    }

    public static function heightTooSmall(int $min, int $actual): self
    {
        return new self(
            ValidationErrorCodeEnum::HeightTooSmall,
            sprintf('Image height %dpx is smaller than required %dpx.', $actual, $min),
            expected: (string) $min,
            actual:   (string) $actual,
        );
    }

    public static function widthTooLarge(int $max, int $actual): self
    {
        return new self(
            ValidationErrorCodeEnum::WidthTooLarge,
            sprintf('Image width %dpx exceeds the allowed %dpx.', $actual, $max),
            expected: (string) $max,
            actual:   (string) $actual,
        );
    }

    public static function heightTooLarge(int $max, int $actual): self
    {
        return new self(
            ValidationErrorCodeEnum::HeightTooLarge,
            sprintf('Image height %dpx exceeds the allowed %dpx.', $actual, $max),
            expected: (string) $max,
            actual:   (string) $actual,
        );
    }

    public static function fileTooLarge(int $maxBytes, int $actualBytes): self
    {
        return new self(
            ValidationErrorCodeEnum::FileTooLarge,
            sprintf('File size %d bytes exceeds the allowed %d bytes.', $actualBytes, $maxBytes),
            expected: (string) $maxBytes,
            actual:   (string) $actualBytes,
        );
    }

    // -------------------------------------------------------------------------
    // Rule failure constructors (phase 9 — aspect ratio + transparency)
    // -------------------------------------------------------------------------

    public static function aspectRatioTooNarrow(float $actual, float $min): self
    {
        return new self(
            ValidationErrorCodeEnum::AspectRatioTooNarrow,
            sprintf(
                'Image aspect ratio %.4f is narrower than the required minimum %.4f.',
                $actual,
                $min,
            ),
            expected: number_format($min, 4),
            actual:   number_format($actual, 4),
        );
    }

    public static function aspectRatioTooWide(float $actual, float $max): self
    {
        return new self(
            ValidationErrorCodeEnum::AspectRatioTooWide,
            sprintf(
                'Image aspect ratio %.4f is wider than the allowed maximum %.4f.',
                $actual,
                $max,
            ),
            expected: number_format($max, 4),
            actual:   number_format($actual, 4),
        );
    }

    public static function transparencyRequired(string $detectedMimeType): self
    {
        return new self(
            ValidationErrorCodeEnum::TransparencyRequired,
            sprintf(
                'This profile requires a transparency-capable format (PNG or WebP); detected "%s".',
                $detectedMimeType,
            ),
            expected: 'image/png or image/webp',
            actual:   $detectedMimeType,
        );
    }

    // -------------------------------------------------------------------------
    // JsonSerializable
    // -------------------------------------------------------------------------

    /**
     * @return array{code: string, message: string, expected: ?string, actual: ?string}
     */
    public function jsonSerialize(): array
    {
        return [
            'code'     => $this->code->value,
            'message'  => $this->message,
            'expected' => $this->expected,
            'actual'   => $this->actual,
        ];
    }
}
