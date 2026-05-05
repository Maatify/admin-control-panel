<?php

declare(strict_types=1);

namespace Maatify\Geo\Bootstrap;

use DI\Container;
use DI\ContainerBuilder;
use Maatify\Geo\Contract\GeoCommandRepositoryInterface;
use Maatify\Geo\Contract\GeoQueryReaderInterface;
use Maatify\Geo\Infrastructure\Repository\PdoGeoCommandRepository;
use Maatify\Geo\Infrastructure\Repository\PdoGeoQueryReader;
use Maatify\Geo\Service\GeoCommandService;
use Maatify\Geo\Service\GeoQueryService;
use PDO;
use Psr\Container\ContainerInterface;

/**
 * Registers all Geo module service bindings into a DI ContainerBuilder.
 *
 * --------------------------------------------------------------------------
 * PURPOSE
 * --------------------------------------------------------------------------
 * This class acts as the Composition Root adapter for the Geo module.
 * It maps Geo contracts (interfaces) to their PDO/MySQL implementations.
 *
 * --------------------------------------------------------------------------
 * DESIGN PRINCIPLES
 * --------------------------------------------------------------------------
 * - The module remains container-agnostic.
 * - No dependency on any host kernel.
 * - language_id is a plain int — no dependency on the `languages` table schema.
 * - Safe for extraction as a standalone library.
 *
 * --------------------------------------------------------------------------
 * HOST REQUIREMENTS
 * --------------------------------------------------------------------------
 * The host application MUST provide:
 * - PDO binding
 * - `languages` table in the same database (used only by admin translation
 *   listing queries that JOIN on language_id)
 *
 * --------------------------------------------------------------------------
 * HOST CUSTOMIZATION
 * --------------------------------------------------------------------------
 * A host application MAY override any binding after calling register():
 *
 *   GeoBindings::register($builder);
 *   $builder->addDefinitions([
 *       GeoQueryReaderInterface::class => MyCustomGeoQueryReader::class,
 *   ]);
 */
final class GeoBindings
{
    /**
     * @param ContainerBuilder<Container> $builder
     */
    public static function register(ContainerBuilder $builder): void
    {
        $builder->addDefinitions([

            // --- Infrastructure -----------------------------------------

            GeoQueryReaderInterface::class => static function (ContainerInterface $c): PdoGeoQueryReader {
                /** @var PDO $pdo */
                $pdo = $c->get(PDO::class);

                return new PdoGeoQueryReader($pdo);
            },

            GeoCommandRepositoryInterface::class => static function (ContainerInterface $c): PdoGeoCommandRepository {
                /** @var PDO $pdo */
                $pdo = $c->get(PDO::class);

                /** @var GeoQueryReaderInterface $queryReader */
                $queryReader = $c->get(GeoQueryReaderInterface::class);

                return new PdoGeoCommandRepository($pdo, $queryReader);
            },

            // --- Services -----------------------------------------------

            GeoQueryService::class => static function (ContainerInterface $c): GeoQueryService {
                /** @var GeoQueryReaderInterface $reader */
                $reader = $c->get(GeoQueryReaderInterface::class);

                return new GeoQueryService($reader);
            },

            GeoCommandService::class => static function (ContainerInterface $c): GeoCommandService {
                /** @var GeoCommandRepositoryInterface $commandRepo */
                $commandRepo = $c->get(GeoCommandRepositoryInterface::class);

                /** @var GeoQueryReaderInterface $queryReader */
                $queryReader = $c->get(GeoQueryReaderInterface::class);

                return new GeoCommandService($commandRepo, $queryReader);
            },

        ]);
    }
}
