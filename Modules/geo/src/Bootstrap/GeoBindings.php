<?php

declare(strict_types=1);

namespace Maatify\Geo\Bootstrap;

use DI\Container;
use DI\ContainerBuilder;
use Maatify\Geo\Contract\CityDropdownRepositoryInterface;
use Maatify\Geo\Contract\CityRepositoryInterface;
use Maatify\Geo\Contract\CityTranslationRepositoryInterface;
use Maatify\Geo\Contract\CountryDropdownRepositoryInterface;
use Maatify\Geo\Contract\CountryRepositoryInterface;
use Maatify\Geo\Contract\CountryTranslationRepositoryInterface;
use Maatify\Geo\Infrastructure\Repository\PdoCityRepository;
use Maatify\Geo\Infrastructure\Repository\PdoCityTranslationRepository;
use Maatify\Geo\Infrastructure\Repository\PdoCountryRepository;
use Maatify\Geo\Infrastructure\Repository\PdoCountryTranslationRepository;
use Maatify\Geo\Service\GeoCommandService;
use Maatify\Geo\Service\GeoQueryService;
use PDO;
use Psr\Container\ContainerInterface;

/**
 * Registers all Geo module service bindings into a DI ContainerBuilder.
 *
 * --------------------------------------------------------------------------
 * INTERFACE → IMPLEMENTATION MAP
 * --------------------------------------------------------------------------
 * CountryRepositoryInterface          → PdoCountryRepository
 * CountryDropdownRepositoryInterface  → PdoCountryRepository  (same instance)
 * CountryTranslationRepositoryInterface → PdoCountryTranslationRepository
 * CityRepositoryInterface             → PdoCityRepository
 * CityDropdownRepositoryInterface     → PdoCityRepository     (same instance)
 * CityTranslationRepositoryInterface  → PdoCityTranslationRepository
 *
 * --------------------------------------------------------------------------
 * DESIGN PRINCIPLES
 * --------------------------------------------------------------------------
 * - The module remains container-agnostic.
 * - No dependency on any host kernel.
 * - language_id is a plain int — no dependency on the `languages` table schema.
 * - Safe for extraction as a standalone library.
 */
final class GeoBindings
{
    /**
     * @param ContainerBuilder<Container> $builder
     */
    public static function register(ContainerBuilder $builder): void
    {
        $builder->addDefinitions([

            // --- Country repositories ------------------------------------

            CountryRepositoryInterface::class => static function (ContainerInterface $c): PdoCountryRepository {
                /** @var PDO $pdo */
                $pdo = $c->get(PDO::class);
                return new PdoCountryRepository($pdo);
            },

            // Dropdown re-uses the same PdoCountryRepository instance (it implements both interfaces).
            CountryDropdownRepositoryInterface::class => static function (ContainerInterface $c): PdoCountryRepository {
                /** @var PdoCountryRepository $repo */
                $repo = $c->get(CountryRepositoryInterface::class);
                return $repo;
            },

            CountryTranslationRepositoryInterface::class => static function (ContainerInterface $c): PdoCountryTranslationRepository {
                /** @var PDO $pdo */
                $pdo = $c->get(PDO::class);
                return new PdoCountryTranslationRepository($pdo);
            },

            // --- City repositories ---------------------------------------

            CityRepositoryInterface::class => static function (ContainerInterface $c): PdoCityRepository {
                /** @var PDO $pdo */
                $pdo = $c->get(PDO::class);
                return new PdoCityRepository($pdo);
            },

            // Dropdown re-uses the same PdoCityRepository instance (it implements both interfaces).
            CityDropdownRepositoryInterface::class => static function (ContainerInterface $c): PdoCityRepository {
                /** @var PdoCityRepository $repo */
                $repo = $c->get(CityRepositoryInterface::class);
                return $repo;
            },

            CityTranslationRepositoryInterface::class => static function (ContainerInterface $c): PdoCityTranslationRepository {
                /** @var PDO $pdo */
                $pdo = $c->get(PDO::class);
                return new PdoCityTranslationRepository($pdo);
            },

            // --- Services -----------------------------------------------

            GeoQueryService::class => static function (ContainerInterface $c): GeoQueryService {
                /** @var CountryRepositoryInterface $countryRepo */
                $countryRepo = $c->get(CountryRepositoryInterface::class);

                /** @var CountryDropdownRepositoryInterface $countryDropdown */
                $countryDropdown = $c->get(CountryDropdownRepositoryInterface::class);

                /** @var CountryTranslationRepositoryInterface $countryTranslationRepo */
                $countryTranslationRepo = $c->get(CountryTranslationRepositoryInterface::class);

                /** @var CityRepositoryInterface $cityRepo */
                $cityRepo = $c->get(CityRepositoryInterface::class);

                /** @var CityDropdownRepositoryInterface $cityDropdown */
                $cityDropdown = $c->get(CityDropdownRepositoryInterface::class);

                /** @var CityTranslationRepositoryInterface $cityTranslationRepo */
                $cityTranslationRepo = $c->get(CityTranslationRepositoryInterface::class);

                return new GeoQueryService(
                    $countryRepo,
                    $countryDropdown,
                    $countryTranslationRepo,
                    $cityRepo,
                    $cityDropdown,
                    $cityTranslationRepo,
                );
            },

            GeoCommandService::class => static function (ContainerInterface $c): GeoCommandService {
                /** @var CountryRepositoryInterface $countryRepo */
                $countryRepo = $c->get(CountryRepositoryInterface::class);

                /** @var CountryTranslationRepositoryInterface $countryTranslationRepo */
                $countryTranslationRepo = $c->get(CountryTranslationRepositoryInterface::class);

                /** @var CityRepositoryInterface $cityRepo */
                $cityRepo = $c->get(CityRepositoryInterface::class);

                /** @var CityTranslationRepositoryInterface $cityTranslationRepo */
                $cityTranslationRepo = $c->get(CityTranslationRepositoryInterface::class);

                return new GeoCommandService(
                    $countryRepo,
                    $countryTranslationRepo,
                    $cityRepo,
                    $cityTranslationRepo,
                );
            },

        ]);
    }
}
