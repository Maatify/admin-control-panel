<?php

declare(strict_types=1);

namespace Maatify\Geo\Tests\Unit\Service;

use Maatify\Geo\Contract\CityDropdownRepositoryInterface;
use Maatify\Geo\Contract\CityRepositoryInterface;
use Maatify\Geo\Contract\CityTranslationRepositoryInterface;
use Maatify\Geo\Contract\CountryDropdownRepositoryInterface;
use Maatify\Geo\Contract\CountryRepositoryInterface;
use Maatify\Geo\Contract\CountryTranslationRepositoryInterface;
use Maatify\Geo\DTO\CityDTO;
use Maatify\Geo\DTO\CountryDTO;
use Maatify\Geo\Exception\CityNotFoundException;
use Maatify\Geo\Exception\CountryNotFoundException;
use Maatify\Geo\Service\GeoQueryService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(GeoQueryService::class)]
final class GeoQueryServiceTest extends TestCase
{
    private CountryRepositoryInterface&MockObject            $countryRepo;
    private CountryDropdownRepositoryInterface&MockObject    $countryDropdown;
    private CountryTranslationRepositoryInterface&MockObject $countryTranslationRepo;
    private CityRepositoryInterface&MockObject               $cityRepo;
    private CityDropdownRepositoryInterface&MockObject       $cityDropdown;
    private CityTranslationRepositoryInterface&MockObject    $cityTranslationRepo;
    private GeoQueryService                                  $service;

    protected function setUp(): void
    {
        $this->countryRepo            = $this->createMock(CountryRepositoryInterface::class);
        $this->countryDropdown        = $this->createMock(CountryDropdownRepositoryInterface::class);
        $this->countryTranslationRepo = $this->createMock(CountryTranslationRepositoryInterface::class);
        $this->cityRepo               = $this->createMock(CityRepositoryInterface::class);
        $this->cityDropdown           = $this->createMock(CityDropdownRepositoryInterface::class);
        $this->cityTranslationRepo    = $this->createMock(CityTranslationRepositoryInterface::class);

        $this->service = new GeoQueryService(
            $this->countryRepo,
            $this->countryDropdown,
            $this->countryTranslationRepo,
            $this->cityRepo,
            $this->cityDropdown,
            $this->cityTranslationRepo,
        );
    }

    // ------------------------------------------------------------------ //
    //  Countries
    // ------------------------------------------------------------------ //

    #[Test]
    public function getCountryById_whenFound_returnsDTO(): void
    {
        $expected = $this->makeCountryDTO(1, 'EG', 'Egypt');

        $this->countryRepo
            ->method('findCountryById')
            ->with(1, null)
            ->willReturn($expected);

        $result = $this->service->getCountryById(1);

        self::assertSame($expected, $result);
    }

    #[Test]
    public function getCountryById_whenNotFound_throwsCountryNotFoundException(): void
    {
        $this->expectException(CountryNotFoundException::class);

        $this->countryRepo
            ->method('findCountryById')
            ->willReturn(null);

        $this->service->getCountryById(999);
    }

    #[Test]
    public function getCountryByCode_whenFound_returnsDTO(): void
    {
        $expected = $this->makeCountryDTO(1, 'EG', 'Egypt');

        $this->countryRepo
            ->method('findCountryByCode')
            ->with('EG', null)
            ->willReturn($expected);

        $result = $this->service->getCountryByCode('EG');

        self::assertSame($expected, $result);
    }

    #[Test]
    public function getCountryByCode_whenNotFound_throwsCountryNotFoundException(): void
    {
        $this->expectException(CountryNotFoundException::class);

        $this->countryRepo
            ->method('findCountryByCode')
            ->willReturn(null);

        $this->service->getCountryByCode('XX');
    }

    #[Test]
    public function activeCountries_delegatesToDropdownRepository(): void
    {
        $expected = [$this->makeCountryDTO(1, 'EG', 'Egypt')];

        $this->countryDropdown
            ->method('listActiveCountries')
            ->with(null)
            ->willReturn($expected);

        $result = $this->service->activeCountries();

        self::assertSame($expected, $result);
    }

    // ------------------------------------------------------------------ //
    //  Cities
    // ------------------------------------------------------------------ //

    #[Test]
    public function getCityById_whenFound_returnsDTO(): void
    {
        $expected = $this->makeCityDTO(5, 1, 'Cairo');

        $this->cityRepo
            ->method('findCityById')
            ->with(5, null)
            ->willReturn($expected);

        $result = $this->service->getCityById(5);

        self::assertSame($expected, $result);
    }

    #[Test]
    public function getCityById_whenNotFound_throwsCityNotFoundException(): void
    {
        $this->expectException(CityNotFoundException::class);

        $this->cityRepo
            ->method('findCityById')
            ->willReturn(null);

        $this->service->getCityById(999);
    }

    #[Test]
    public function activeCitiesByCountryId_delegatesToDropdownRepository(): void
    {
        $expected = [$this->makeCityDTO(5, 1, 'Cairo')];

        $this->cityDropdown
            ->method('listActiveCitiesByCountryId')
            ->with(1, null)
            ->willReturn($expected);

        $result = $this->service->activeCitiesByCountryId(1);

        self::assertSame($expected, $result);
    }

    #[Test]
    public function activeCitiesByCountryCode_delegatesToDropdownRepository(): void
    {
        $expected = [$this->makeCityDTO(5, 1, 'Cairo')];

        $this->cityDropdown
            ->method('listActiveCitiesByCountryCode')
            ->with('EG', null)
            ->willReturn($expected);

        $result = $this->service->activeCitiesByCountryCode('EG');

        self::assertSame($expected, $result);
    }

    // ------------------------------------------------------------------ //
    //  Helpers
    // ------------------------------------------------------------------ //

    private function makeCountryDTO(int $id, string $code, string $name): CountryDTO
    {
        return new CountryDTO(
            id:             $id,
            code:           $code,
            name:           $name,
            currency:       null,
            icon:           null,
            isActive:       true,
            displayOrder:   $id,
            createdAt:      '2025-01-01 00:00:00',
            updatedAt:      null,
            translatedName: null,
            languageId:     null,
        );
    }

    private function makeCityDTO(int $id, int $countryId, string $name): CityDTO
    {
        return new CityDTO(
            id:             $id,
            countryId:      $countryId,
            code:           null,
            name:           $name,
            timeZone:       null,
            isActive:       true,
            displayOrder:   $id,
            createdAt:      '2025-01-01 00:00:00',
            updatedAt:      null,
            translatedName: null,
            languageId:     null,
        );
    }
}

