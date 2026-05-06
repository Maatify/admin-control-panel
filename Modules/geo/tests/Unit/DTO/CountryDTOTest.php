<?php

declare(strict_types=1);

namespace Maatify\Geo\Tests\Unit\DTO;

use Maatify\Geo\DTO\CountryDTO;
use Maatify\Geo\Exception\GeoInvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(CountryDTO::class)]
final class CountryDTOTest extends TestCase
{
    // ------------------------------------------------------------------ //
    //  fromRow — happy path
    // ------------------------------------------------------------------ //

    #[Test]
    public function fromRow_withFullRow_createsDTO(): void
    {
        $row = [
            'id'                     => 1,
            'code'                   => 'EG',
            'name'                   => 'Egypt',
            'icon'                   => '🇪🇬',
            'is_active'              => 1,
            'display_order'          => 1,
            'created_at'             => '2025-01-01 00:00:00',
            'updated_at'             => null,
            'translated_name'        => null,
            'translation_language_id' => null,
        ];

        $dto = CountryDTO::fromRow($row);

        self::assertSame(1,             $dto->id);
        self::assertSame('EG',          $dto->code);
        self::assertSame('Egypt',       $dto->name);
        self::assertSame('🇪🇬',         $dto->icon);
        self::assertTrue($dto->isActive);
        self::assertSame(1,             $dto->displayOrder);
        self::assertSame('2025-01-01 00:00:00', $dto->createdAt);
        self::assertNull($dto->updatedAt);
        self::assertNull($dto->translatedName);
        self::assertNull($dto->languageId);
    }

    #[Test]
    public function fromRow_withTranslation_populatesTranslatedNameAndLanguageId(): void
    {
        $row = [
            'id'                     => 2,
            'code'                   => 'US',
            'name'                   => 'United States',
            'icon'                   => null,
            'is_active'              => '1',
            'display_order'          => '2',
            'created_at'             => '2025-06-01 00:00:00',
            'updated_at'             => '2025-06-15 00:00:00',
            'translated_name'        => 'الولايات المتحدة',
            'translation_language_id' => 3,
        ];

        $dto = CountryDTO::fromRow($row);

        self::assertSame('الولايات المتحدة', $dto->translatedName);
        self::assertSame(3,                  $dto->languageId);
    }

    // ------------------------------------------------------------------ //
    //  displayName
    // ------------------------------------------------------------------ //

    #[Test]
    public function displayName_withoutTranslation_returnsBaseName(): void
    {
        $dto = $this->makeDTO(name: 'Egypt', translatedName: null);

        self::assertSame('Egypt', $dto->displayName());
    }

    #[Test]
    public function displayName_withTranslation_returnsTranslatedName(): void
    {
        $dto = $this->makeDTO(name: 'Egypt', translatedName: 'مصر');

        self::assertSame('مصر', $dto->displayName());
    }

    // ------------------------------------------------------------------ //
    //  jsonSerialize
    // ------------------------------------------------------------------ //

    #[Test]
    public function jsonSerialize_returnsExpectedKeys(): void
    {
        $dto = $this->makeDTO();

        $serialized = $dto->jsonSerialize();

        self::assertArrayHasKey('id',              $serialized);
        self::assertArrayHasKey('code',            $serialized);
        self::assertArrayHasKey('name',            $serialized);
        self::assertArrayHasKey('icon',            $serialized);
        self::assertArrayHasKey('is_active',       $serialized);
        self::assertArrayHasKey('display_order',   $serialized);
        self::assertArrayHasKey('created_at',      $serialized);
        self::assertArrayHasKey('updated_at',      $serialized);
        self::assertArrayHasKey('translated_name', $serialized);
        self::assertArrayHasKey('language_id',     $serialized);
    }

    // ------------------------------------------------------------------ //
    //  fromRow — invalid types throw GeoInvalidArgumentException
    // ------------------------------------------------------------------ //

    #[Test]
    public function fromRow_withInvalidIdType_throwsGeoInvalidArgumentException(): void
    {
        $this->expectException(GeoInvalidArgumentException::class);

        CountryDTO::fromRow([
            'id'            => 'not-an-int',
            'code'          => 'EG',
            'name'          => 'Egypt',
            'icon'          => null,
            'is_active'     => 1,
            'display_order' => 1,
            'created_at'    => '2025-01-01 00:00:00',
        ]);
    }

    // ------------------------------------------------------------------ //
    //  Helpers
    // ------------------------------------------------------------------ //

    private function makeDTO(
        string  $name          = 'Egypt',
        ?string $translatedName = null,
    ): CountryDTO {
        return new CountryDTO(
            id:             1,
            code:           'EG',
            name:           $name,
            icon:           null,
            isActive:       true,
            displayOrder:   1,
            createdAt:      '2025-01-01 00:00:00',
            updatedAt:      null,
            translatedName: $translatedName,
            languageId:     $translatedName !== null ? 1 : null,
        );
    }
}

