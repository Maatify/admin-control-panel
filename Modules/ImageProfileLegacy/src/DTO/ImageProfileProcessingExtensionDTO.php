<?php

declare(strict_types=1);

namespace Maatify\ImageProfileLegacy\DTO;

use JsonSerializable;
use Maatify\ImageProfileLegacy\Enum\ImageFormatEnum;
use Maatify\ImageProfileLegacy\Exception\InvalidImageInputException;

/**
 * Optional processing extension metadata attached to a profile.
 *
 * This DTO is NOT part of the canonical validation rules contract.
 * It is extension-scope data that can guide optional post-validation processing.
 */
final readonly class ImageProfileProcessingExtensionDTO implements JsonSerializable
{
    public function __construct(
        public ?ImageFormatEnum $preferredFormat = null,
        public ?int $preferredQuality = null,
        public VariantDefinitionCollectionDTO $variants = new VariantDefinitionCollectionDTO(),
    ) {
        if ($this->preferredQuality !== null && ($this->preferredQuality < 1 || $this->preferredQuality > 100)) {
            throw InvalidImageInputException::invalidProcessingOption(
                'preferredQuality',
                'must be between 1 and 100 when provided',
            );
        }
    }

    public function hasVariants(): bool
    {
        return count($this->variants) > 0;
    }

    public function isEmpty(): bool
    {
        return $this->preferredFormat === null
            && $this->preferredQuality === null
            && ! $this->hasVariants();
    }

    /**
     * @return array{preferredFormat: ?string, preferredQuality: ?int, variants: VariantDefinitionCollectionDTO}
     */
    public function jsonSerialize(): array
    {
        return [
            'preferredFormat' => $this->preferredFormat?->value,
            'preferredQuality' => $this->preferredQuality,
            'variants' => $this->variants,
        ];
    }
}
