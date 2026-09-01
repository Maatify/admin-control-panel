<?php

declare(strict_types=1);

namespace Maatify\ImageProfile\Tests;

use Maatify\ImageProfile\DTO\ImageProfileDTO;
use Maatify\ImageProfile\Exception\ImageProfileInvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ImageProfileDTOTest extends TestCase
{
    public function testBoolHydrationAcceptsOnlyStrictDbBooleanValues(): void
    {
        $dto = ImageProfileDTO::fromRow($this->baseRow(['is_active' => '1', 'requires_transparency' => '0']));

        self::assertTrue($dto->isActive);
        self::assertFalse($dto->requiresTransparency);
    }

    public function testBoolHydrationRejectsLooseStringValues(): void
    {
        $this->expectException(ImageProfileInvalidArgumentException::class);

        ImageProfileDTO::fromRow($this->baseRow(['is_active' => 'false']));
    }

    public function testDecimalHydrationAcceptsDatabaseNumericRepresentations(): void
    {
        $dto = ImageProfileDTO::fromRow($this->baseRow([
            'min_aspect_ratio' => 1,
            'max_aspect_ratio' => 1.75,
        ]));

        self::assertSame('1', $dto->minAspectRatio);
        self::assertSame('1.75', $dto->maxAspectRatio);
    }

    public function testJsonSerializationUsesThePublicFieldContract(): void
    {
        $dto = ImageProfileDTO::fromRow($this->baseRow([
            'display_name' => 'Avatar',
            'is_active' => '1',
            'requires_transparency' => '1',
            'min_aspect_ratio' => '1.0000',
        ]));

        $json = $dto->jsonSerialize();

        self::assertSame(1, $json['id']);
        self::assertSame('Avatar', $json['display_name']);
        self::assertTrue($json['is_active']);
        self::assertTrue($json['requires_transparency']);
        self::assertSame('1.0000', $json['min_aspect_ratio']);
        self::assertArrayHasKey('updated_at', $json);
    }

    /**
     * @param array<string, mixed> $override
     * @return array<string, mixed>
     */
    private function baseRow(array $override = []): array
    {
        return array_merge([
            'id' => 1,
            'code' => 'avatar',
            'display_name' => 'Avatar',
            'min_width' => null,
            'min_height' => null,
            'max_width' => null,
            'max_height' => null,
            'max_size_bytes' => null,
            'allowed_extensions' => null,
            'allowed_mime_types' => null,
            'is_active' => 1,
            'notes' => null,
            'min_aspect_ratio' => null,
            'max_aspect_ratio' => null,
            'requires_transparency' => 0,
            'preferred_format' => null,
            'preferred_quality' => null,
            'variants' => null,
            'created_at' => '2026-01-01 00:00:00',
            'updated_at' => null,
        ], $override);
    }
}
