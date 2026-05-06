<?php

declare(strict_types=1);

namespace Maatify\Geo\Tests\Unit\DTO;

use Maatify\Geo\DTO\CountryTranslationDTO;
use Maatify\Geo\Exception\GeoInvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(CountryTranslationDTO::class)]
final class CountryTranslationDTOTest extends TestCase
{
    #[Test]
    public function fromRow_withValidRow_createsDTO(): void
    {
        $row = [
            'id'         => 10,
            'country_id' => 1,
            'language_id'=> 3,
            'name'       => 'مصر',
            'created_at' => '2025-01-01 00:00:00',
            'updated_at' => null,
        ];

        $dto = CountryTranslationDTO::fromRow($row);

        self::assertSame(10,                      $dto->id);
        self::assertSame(1,                       $dto->countryId);
        self::assertSame(3,                       $dto->languageId);
        self::assertSame('مصر',                   $dto->name);
        self::assertSame('2025-01-01 00:00:00',   $dto->createdAt);
        self::assertNull($dto->updatedAt);
    }

    #[Test]
    public function jsonSerialize_returnsExpectedKeys(): void
    {
        $dto = CountryTranslationDTO::fromRow([
            'id'         => 10,
            'country_id' => 1,
            'language_id'=> 3,
            'name'       => 'مصر',
            'created_at' => '2025-01-01 00:00:00',
            'updated_at' => null,
        ]);

        $keys = array_keys($dto->jsonSerialize());

        self::assertContains('id',          $keys);
        self::assertContains('country_id',  $keys);
        self::assertContains('language_id', $keys);
        self::assertContains('name',        $keys);
        self::assertContains('created_at',  $keys);
        self::assertContains('updated_at',  $keys);
    }

    #[Test]
    public function fromRow_withInvalidName_throwsGeoInvalidArgumentException(): void
    {
        $this->expectException(GeoInvalidArgumentException::class);

        CountryTranslationDTO::fromRow([
            'id'         => 1,
            'country_id' => 1,
            'language_id'=> 3,
            'name'       => 999,   // wrong type
            'created_at' => '2025-01-01 00:00:00',
        ]);
    }
}

