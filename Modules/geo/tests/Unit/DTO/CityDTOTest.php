<?php

declare(strict_types=1);

namespace Maatify\Geo\Tests\Unit\DTO;

use Maatify\Geo\DTO\CityDTO;
use Maatify\Geo\Exception\GeoInvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(CityDTO::class)]
final class CityDTOTest extends TestCase
{
    #[Test]
    public function fromRow_withFullRow_createsDTO(): void
    {
        $row = [
            'id'                     => 5,
            'country_id'             => 1,
            'code'                   => 'CAI',
            'name'                   => 'Cairo',
            'is_active'              => 1,
            'display_order'          => 1,
            'created_at'             => '2025-02-01 00:00:00',
            'updated_at'             => null,
            'translated_name'        => null,
            'translation_language_id' => null,
        ];

        $dto = CityDTO::fromRow($row);

        self::assertSame(5,      $dto->id);
        self::assertSame(1,      $dto->countryId);
        self::assertSame('CAI',  $dto->code);
        self::assertSame('Cairo',$dto->name);
        self::assertTrue($dto->isActive);
        self::assertSame(1,      $dto->displayOrder);
        self::assertNull($dto->translatedName);
        self::assertNull($dto->languageId);
    }

    #[Test]
    public function fromRow_withNullCode_setsCodeToNull(): void
    {
        $row = [
            'id'            => 6,
            'country_id'    => 1,
            'code'          => null,
            'name'          => 'Alexandria',
            'is_active'     => 0,
            'display_order' => 2,
            'created_at'    => '2025-02-01 00:00:00',
            'updated_at'    => null,
        ];

        $dto = CityDTO::fromRow($row);

        self::assertNull($dto->code);
        self::assertFalse($dto->isActive);
    }

    #[Test]
    public function displayName_withoutTranslation_returnsBaseName(): void
    {
        $dto = $this->makeDTO(name: 'Cairo', translatedName: null);

        self::assertSame('Cairo', $dto->displayName());
    }

    #[Test]
    public function displayName_withTranslation_returnsTranslatedName(): void
    {
        $dto = $this->makeDTO(name: 'Cairo', translatedName: 'القاهرة');

        self::assertSame('القاهرة', $dto->displayName());
    }

    #[Test]
    public function jsonSerialize_containsAllRequiredKeys(): void
    {
        $dto = $this->makeDTO();

        $keys = array_keys($dto->jsonSerialize());

        self::assertContains('id',              $keys);
        self::assertContains('country_id',      $keys);
        self::assertContains('code',            $keys);
        self::assertContains('name',            $keys);
        self::assertContains('is_active',       $keys);
        self::assertContains('display_order',   $keys);
        self::assertContains('created_at',      $keys);
        self::assertContains('updated_at',      $keys);
        self::assertContains('translated_name', $keys);
        self::assertContains('language_id',     $keys);
    }

    #[Test]
    public function fromRow_withInvalidId_throwsGeoInvalidArgumentException(): void
    {
        $this->expectException(GeoInvalidArgumentException::class);

        CityDTO::fromRow([
            'id'            => 'bad',
            'country_id'    => 1,
            'name'          => 'Cairo',
            'is_active'     => 1,
            'display_order' => 1,
            'created_at'    => '2025-01-01 00:00:00',
        ]);
    }

    private function makeDTO(
        string  $name           = 'Cairo',
        ?string $translatedName = null,
    ): CityDTO {
        return new CityDTO(
            id:             5,
            countryId:      1,
            code:           'CAI',
            name:           $name,
            isActive:       true,
            displayOrder:   1,
            createdAt:      '2025-02-01 00:00:00',
            updatedAt:      null,
            translatedName: $translatedName,
            languageId:     $translatedName !== null ? 1 : null,
        );
    }
}

