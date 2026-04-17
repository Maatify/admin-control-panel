<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/image-profile
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-04-17
 */

declare(strict_types=1);

namespace Maatify\ImageProfileLegacy\DTO;

use JsonSerializable;
use Maatify\ImageProfileLegacy\Enum\ImageFormatEnum;
use Maatify\ImageProfileLegacy\Enum\ResizeModeEnum;
use Maatify\ImageProfileLegacy\Exception\InvalidImageInputException;

/**
 * Describes how an image should be resized.
 *
 * @psalm-immutable
 */
final readonly class ResizeOptionsDTO implements JsonSerializable
{
    /**
     * @param int               $width        Target width in pixels (must be > 0).
     * @param int               $height       Target height in pixels (must be > 0).
     * @param ResizeModeEnum    $mode         Resize strategy.
     * @param int               $quality      Output quality 1–100.
     * @param ImageFormatEnum|null $outputFormat Output format; null = keep source format.
     */
    public function __construct(
        public int $width,
        public int $height,
        public ResizeModeEnum $mode = ResizeModeEnum::Fit,
        public int $quality = 85,
        public ?ImageFormatEnum $outputFormat = null,
    ) {
        if ($this->width <= 0) {
            throw InvalidImageInputException::invalidProcessingOption('width', 'must be a positive integer');
        }

        if ($this->height <= 0) {
            throw InvalidImageInputException::invalidProcessingOption('height', 'must be a positive integer');
        }

        if ($this->quality < 1 || $this->quality > 100) {
            throw InvalidImageInputException::invalidProcessingOption('quality', 'must be between 1 and 100');
        }
    }

    // -------------------------------------------------------------------------
    // Named constructors
    // -------------------------------------------------------------------------

    public static function fit(int $width, int $height, int $quality = 85): self
    {
        return new self($width, $height, ResizeModeEnum::Fit, $quality);
    }

    public static function fill(int $width, int $height, int $quality = 85): self
    {
        return new self($width, $height, ResizeModeEnum::Fill, $quality);
    }

    public static function webpThumbnail(int $width, int $height, int $quality = 80): self
    {
        return new self($width, $height, ResizeModeEnum::Fill, $quality, ImageFormatEnum::Webp);
    }

    // -------------------------------------------------------------------------
    // JsonSerializable
    // -------------------------------------------------------------------------

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'width'        => $this->width,
            'height'       => $this->height,
            'mode'         => $this->mode->value,
            'quality'      => $this->quality,
            'outputFormat' => $this->outputFormat?->value,
        ];
    }
}
