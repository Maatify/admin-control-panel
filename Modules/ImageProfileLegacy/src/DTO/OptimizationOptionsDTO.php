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
use Maatify\ImageProfileLegacy\Exception\InvalidImageInputException;

/**
 * Describes how an image should be optimised.
 *
 * @psalm-immutable
 */
final readonly class OptimizationOptionsDTO implements JsonSerializable
{
    /**
     * @param int                 $quality      Output quality 1–100.
     * @param bool                $stripMetadata Re-encode without EXIF/XMP/IPTC.
     * @param ImageFormatEnum|null $targetFormat  Null = keep source format.
     */
    public function __construct(
        public int $quality = 85,
        public bool $stripMetadata = true,
        public ?ImageFormatEnum $targetFormat = null,
    ) {
        if ($this->quality < 1 || $this->quality > 100) {
            throw InvalidImageInputException::invalidProcessingOption('quality', 'must be between 1 and 100');
        }
    }

    // -------------------------------------------------------------------------
    // Named constructors
    // -------------------------------------------------------------------------

    public static function recompress(int $quality = 85): self
    {
        return new self($quality, true);
    }

    public static function toWebp(int $quality = 80): self
    {
        return new self($quality, true, ImageFormatEnum::Webp);
    }

    public static function lossless(int $quality = 90): self
    {
        return new self($quality, false);
    }

    // -------------------------------------------------------------------------
    // JsonSerializable
    // -------------------------------------------------------------------------

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'quality'       => $this->quality,
            'stripMetadata' => $this->stripMetadata,
            'targetFormat'  => $this->targetFormat?->value,
        ];
    }
}
