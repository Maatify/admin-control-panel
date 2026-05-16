<?php

declare(strict_types=1);

namespace Maatify\Geo\Tests\Unit\Service;

use Maatify\Geo\Command\CreateCountryCommand;
use Maatify\Geo\Command\DeleteCountryTranslationCommand;
use Maatify\Geo\Command\UpdateCountryStatusCommand;
use Maatify\Geo\Contract\CityRepositoryInterface;
use Maatify\Geo\Contract\CityTranslationRepositoryInterface;
use Maatify\Geo\Contract\CountryRepositoryInterface;
use Maatify\Geo\Contract\CountryTranslationRepositoryInterface;
use Maatify\Geo\DTO\CountryDTO;
use Maatify\Geo\Exception\CountryCodeAlreadyExistsException;
use Maatify\Geo\Exception\CountryNotFoundException;
use Maatify\Geo\Service\GeoCommandService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(GeoCommandService::class)]
final class GeoCommandServiceTest extends TestCase
{
    private CountryRepositoryInterface&MockObject            $countryRepo;
    private CountryTranslationRepositoryInterface&MockObject $countryTranslationRepo;
    private CityRepositoryInterface&MockObject               $cityRepo;
    private CityTranslationRepositoryInterface&MockObject    $cityTranslationRepo;
    private GeoCommandService                                $service;

    protected function setUp(): void
    {
        $this->countryRepo            = $this->createMock(CountryRepositoryInterface::class);
        $this->countryTranslationRepo = $this->createMock(CountryTranslationRepositoryInterface::class);
        $this->cityRepo               = $this->createMock(CityRepositoryInterface::class);
        $this->cityTranslationRepo    = $this->createMock(CityTranslationRepositoryInterface::class);

        $this->service = new GeoCommandService(
            $this->countryRepo,
            $this->countryTranslationRepo,
            $this->cityRepo,
            $this->cityTranslationRepo,
        );
    }

    // ------------------------------------------------------------------ //
    //  createCountry
    // ------------------------------------------------------------------ //

    #[Test]
    public function createCountry_whenCodeIsUnique_delegatesToRepository(): void
    {
        $command  = new CreateCountryCommand(code: 'eg', name: 'Egypt', icon: null, isActive: true);
        $expected = $this->makeCountryDTO(1, 'EG', 'Egypt');

        // No existing country with code EG
        $this->countryRepo
            ->method('findCountryByCode')
            ->with('EG')
            ->willReturn(null);

        // Repository gets called with uppercased code
        $this->countryRepo
            ->expects(self::once())
            ->method('createCountry')
            ->willReturn($expected);

        $result = $this->service->createCountry($command);

        self::assertSame($expected, $result);
    }

    #[Test]
    public function createCountry_whenCodeAlreadyExists_throwsCountryCodeAlreadyExistsException(): void
    {
        $this->expectException(CountryCodeAlreadyExistsException::class);

        $existing = $this->makeCountryDTO(1, 'EG', 'Egypt');

        $this->countryRepo
            ->method('findCountryByCode')
            ->with('EG')
            ->willReturn($existing);

        $this->service->createCountry(
            new CreateCountryCommand(code: 'eg', name: 'Another Egypt', icon: null, isActive: true)
        );
    }

    // ------------------------------------------------------------------ //
    //  updateCountryStatus
    // ------------------------------------------------------------------ //

    #[Test]
    public function updateCountryStatus_whenCountryExists_delegatesToRepository(): void
    {
        $existingDTO = $this->makeCountryDTO(1, 'EG', 'Egypt');
        $updatedDTO  = $this->makeCountryDTO(1, 'EG', 'Egypt');

        $this->countryRepo->method('findCountryById')->willReturn($existingDTO);
        $this->countryRepo->method('updateCountryStatus')->willReturn($updatedDTO);

        $command = new UpdateCountryStatusCommand(id: 1, isActive: false);
        $result  = $this->service->updateCountryStatus($command);

        self::assertSame($updatedDTO, $result);
    }

    #[Test]
    public function updateCountryStatus_whenCountryNotFound_throwsCountryNotFoundException(): void
    {
        $this->expectException(CountryNotFoundException::class);

        $this->countryRepo->method('findCountryById')->willReturn(null);

        $this->service->updateCountryStatus(new UpdateCountryStatusCommand(id: 99, isActive: false));
    }

    // ------------------------------------------------------------------ //
    //  reorderCountry
    // ------------------------------------------------------------------ //

    #[Test]
    public function reorderCountry_withInvalidOrder_throwsInvalidArgumentException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service->reorderCountry(1, 0);
    }

    #[Test]
    public function reorderCountry_whenCountryNotFound_throwsCountryNotFoundException(): void
    {
        $this->expectException(CountryNotFoundException::class);

        $this->countryRepo->method('findCountryById')->willReturn(null);

        $this->service->reorderCountry(99, 1);
    }

    // ------------------------------------------------------------------ //
    //  deleteCountryTranslation
    // ------------------------------------------------------------------ //

    #[Test]
    public function deleteCountryTranslation_whenCountryNotFound_throwsCountryNotFoundException(): void
    {
        $this->expectException(CountryNotFoundException::class);

        $this->countryRepo->method('findCountryById')->willReturn(null);

        $this->service->deleteCountryTranslation(
            new DeleteCountryTranslationCommand(countryId: 99, languageId: 1)
        );
    }

    #[Test]
    public function deleteCountryTranslation_whenCountryExists_delegatesToRepository(): void
    {
        $existingDTO = $this->makeCountryDTO(1, 'EG', 'Egypt');

        $this->countryRepo->method('findCountryById')->willReturn($existingDTO);
        $this->countryTranslationRepo->expects(self::once())->method('deleteCountryTranslation');

        $this->service->deleteCountryTranslation(
            new DeleteCountryTranslationCommand(countryId: 1, languageId: 3)
        );
    }

    // ------------------------------------------------------------------ //
    //  Helpers
    // ------------------------------------------------------------------ //

    private function makeCountryDTO(int $id, string $code, string $name): CountryDTO
    {
        return new CountryDTO(
            id:                 $id,
            code:               $code,
            name:               $name,
            phoneCode:          null,
            currency:           null,
            icon:               null,
            isActive:           true,
            displayOrder:       $id,
            createdAt:          '2025-01-01 00:00:00',
            updatedAt:          null,
            translatedName:     null,
            languageId:         null,
            isStateRequired:    false,
            isPostcodeRequired: false,
        );
    }
}

