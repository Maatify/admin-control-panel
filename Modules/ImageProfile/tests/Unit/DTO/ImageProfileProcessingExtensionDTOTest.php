<?php

declare(strict_types=1);

namespace Maatify\ImageProfile\Tests\Unit\DTO;

use Maatify\ImageProfile\DTO\ImageProfileProcessingExtensionDTO;
use Maatify\ImageProfile\DTO\ResizeOptionsDTO;
use Maatify\ImageProfile\DTO\VariantDefinitionCollectionDTO;
use Maatify\ImageProfile\DTO\VariantDefinitionDTO;
use Maatify\ImageProfile\Enum\ImageFormatEnum;
use Maatify\ImageProfile\Exception\InvalidImageInputException;
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
