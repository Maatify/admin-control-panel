<?php

declare(strict_types=1);

namespace Maatify\Geo\Tests\Unit\DTO;

use Maatify\Geo\DTO\CityTranslationDTO;
use Maatify\Geo\Exception\GeoInvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(CityTranslationDTO::class)]
final class CityTranslationDTOTest extends TestCase
{
    #[Test]
    public function fromRow_withValidRow_createsDTO(): void
    {
        $row = [
            'id'          => 7,
            'city_id'     => 5,
            'language_id' => 3,
            'name'        => 'القاهرة',
            'created_at'  => '2025-02-01 00:00:00',
            'updated_at'  => null,
        ];

        $dto = CityTranslationDTO::fromRow($row);

        self::assertSame(7,                        $dto->id);
        self::assertSame(5,                        $dto->cityId);
        self::assertSame(3,                        $dto->languageId);
        self::assertSame('القاهرة',                $dto->name);
        self::assertSame('2025-02-01 00:00:00',    $dto->createdAt);
        self::assertNull($dto->updatedAt);
    }

    #[Test]
    public function fromRow_withUpdatedAt_populatesTimestamp(): void
    {
        $row = [
            'id'          => 8,
            'city_id'     => 5,
            'language_id' => 2,
            'name'        => 'الإسكندرية',
            'created_at'  => '2025-03-01 00:00:00',
            'updated_at'  => '2025-04-15 12:00:00',
        ];

        $dto = CityTranslationDTO::fromRow($row);

        self::assertSame('2025-04-15 12:00:00', $dto->updatedAt);
    }

    #[Test]
    public function fromRow_withNumericStringId_castsToInt(): void
    {
        $row = [
            'id'          => '9',
            'city_id'     => '5',
            'language_id' => '3',
            'name'        => 'القاهرة',
            'created_at'  => '2025-02-01 00:00:00',
            'updated_at'  => null,
        ];

        $dto = CityTranslationDTO::fromRow($row);

        self::assertSame(9, $dto->id);
        self::assertSame(5, $dto->cityId);
        self::assertSame(3, $dto->languageId);
    }

    #[Test]
    public function jsonSerialize_returnsExpectedKeys(): void
    {
        $dto = CityTranslationDTO::fromRow([
            'id'          => 7,
            'city_id'     => 5,
            'language_id' => 3,
            'name'        => 'القاهرة',
            'created_at'  => '2025-02-01 00:00:00',
            'updated_at'  => null,
        ]);

        $keys = array_keys($dto->jsonSerialize());

        self::assertContains('id',          $keys);
        self::assertContains('city_id',     $keys);
        self::assertContains('language_id', $keys);
        self::assertContains('name',        $keys);
        self::assertContains('created_at',  $keys);
        self::assertContains('updated_at',  $keys);
    }

    #[Test]
    public function jsonSerialize_valuesMatchProperties(): void
    {
        $dto = new CityTranslationDTO(
            id:         7,
            cityId:     5,
            languageId: 3,
            name:       'القاهرة',
            createdAt:  '2025-02-01 00:00:00',
            updatedAt:  null,
        );

        $data = $dto->jsonSerialize();

        self::assertSame(7,                     $data['id']);
        self::assertSame(5,                     $data['city_id']);
        self::assertSame(3,                     $data['language_id']);
        self::assertSame('القاهرة',             $data['name']);
        self::assertSame('2025-02-01 00:00:00', $data['created_at']);
        self::assertNull($data['updated_at']);
    }

    #[Test]
    public function fromRow_withInvalidName_throwsGeoInvalidArgumentException(): void
    {
        $this->expectException(GeoInvalidArgumentException::class);

        CityTranslationDTO::fromRow([
            'id'          => 1,
            'city_id'     => 5,
            'language_id' => 3,
            'name'        => 999,   // wrong type
            'created_at'  => '2025-02-01 00:00:00',
        ]);
    }

    #[Test]
    public function fromRow_withInvalidId_throwsGeoInvalidArgumentException(): void
    {
        $this->expectException(GeoInvalidArgumentException::class);

        CityTranslationDTO::fromRow([
            'id'          => 'not-an-int',
            'city_id'     => 5,
            'language_id' => 3,
            'name'        => 'القاهرة',
            'created_at'  => '2025-02-01 00:00:00',
        ]);
    }

    #[Test]
    public function fromRow_withInvalidCityId_throwsGeoInvalidArgumentException(): void
    {
        $this->expectException(GeoInvalidArgumentException::class);

        CityTranslationDTO::fromRow([
            'id'          => 1,
            'city_id'     => 'bad',
            'language_id' => 3,
            'name'        => 'القاهرة',
            'created_at'  => '2025-02-01 00:00:00',
        ]);
    }
}

