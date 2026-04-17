<?php

declare(strict_types=1);

namespace ImageProfileLegacy\tests\Unit\DTO;

use Maatify\ImageProfileLegacy\DTO\ImageProfileProcessingExtensionDTO;
use Maatify\ImageProfileLegacy\DTO\ResizeOptionsDTO;
use Maatify\ImageProfileLegacy\DTO\VariantDefinitionCollectionDTO;
use Maatify\ImageProfileLegacy\DTO\VariantDefinitionDTO;
use Maatify\ImageProfileLegacy\Enum\ImageFormatEnum;
use Maatify\ImageProfileLegacy\Exception\InvalidImageInputException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ImageProfileProcessingExtensionDTO::class)]
final class ImageProfileProcessingExtensionDTOTest extends TestCase
{
    public function testEmptyExtensionReportsEmpty(): void
    {
        $dto = new ImageProfileProcessingExtensionDTO();

        self::assertTrue($dto->isEmpty());
        self::assertFalse($dto->hasVariants());
    }

    public function testExtensionWithFormatQualityAndVariantsIsNotEmpty(): void
    {
        $variants = new VariantDefinitionCollectionDTO(
            new VariantDefinitionDTO('thumb', ResizeOptionsDTO::webpThumbnail(120, 120)),
        );

        $dto = new ImageProfileProcessingExtensionDTO(
            preferredFormat: ImageFormatEnum::Webp,
            preferredQuality: 85,
            variants: $variants,
        );

        self::assertFalse($dto->isEmpty());
        self::assertTrue($dto->hasVariants());
    }

    public function testInvalidPreferredQualityThrows(): void
    {
        $this->expectException(InvalidImageInputException::class);

        new ImageProfileProcessingExtensionDTO(preferredQuality: 101);
    }
}
