<?php

declare(strict_types=1);

namespace Maatify\Currency\Bootstrap;

use DI\Container;
use DI\ContainerBuilder;
use Maatify\Currency\Contract\CurrencyCommandRepositoryInterface;
use Maatify\Currency\Contract\CurrencyQueryReaderInterface;
use Maatify\Currency\Infrastructure\Repository\PdoCurrencyCommandRepository;
use Maatify\Currency\Infrastructure\Repository\PdoCurrencyQueryReader;
use Maatify\Currency\Service\CurrencyCommandService;
use Maatify\Currency\Service\CurrencyQueryService;
use PDO;
use Psr\Container\ContainerInterface;

/**
 * Registers all Currencies module service bindings
 * into a DI ContainerBuilder.
 *
 * --------------------------------------------------------------------------
 * PURPOSE
 * --------------------------------------------------------------------------
 * This class acts as the Composition Root adapter for the
 * Currencies module.
 *
 * It defines how Currency contracts (interfaces) are mapped
 * to their infrastructure implementations (PDO/MySQL repositories).
 *
 * --------------------------------------------------------------------------
 * DESIGN PRINCIPLES
 * --------------------------------------------------------------------------
 * - The module remains container-agnostic.
 * - No dependency on any host kernel.
 * - Only relies on external contracts such as PDO and languages table.
 * - Safe for extraction as a standalone library.
 *
 * --------------------------------------------------------------------------
 * HOST CUSTOMIZATION
 * --------------------------------------------------------------------------
 * A host application MAY:
 *
 * - Override any binding after calling register()
 * - Replace repositories with custom implementations
 * - Swap the persistence layer entirely
 *
 * Example:
 *
 *   CurrenciesBindings::register($builder);
 *   $builder->addDefinitions([
 *       CurrencyQueryReaderInterface::class => MyCustomQueryReader::class,
 *   ]);
 *
 * --------------------------------------------------------------------------
 * IMPORTANT
 * --------------------------------------------------------------------------
 * This class contains NO business logic.
 * It is strictly responsible for dependency wiring.
 *
 * REQUIREMENTS:
 * The host application must provide:
 * - PDO binding
 * - `languages` table in the same database (kernel-grade dependency)
 */
final class CurrenciesBindings
{
    /**
     * @param ContainerBuilder<Container> $builder
     */
    public static function register(ContainerBuilder $builder): void
    {
        $builder->addDefinitions([

            // --- Infrastructure -----------------------------------------

            CurrencyQueryReaderInterface::class => static function (ContainerInterface $c): PdoCurrencyQueryReader {
                /** @var PDO $pdo */
                $pdo = $c->get(PDO::class);

                return new PdoCurrencyQueryReader($pdo);
            },

            CurrencyCommandRepositoryInterface::class => static function (ContainerInterface $c): PdoCurrencyCommandRepository {
                /** @var PDO $pdo */
                $pdo = $c->get(PDO::class);

                /** @var CurrencyQueryReaderInterface $queryReader */
                $queryReader = $c->get(CurrencyQueryReaderInterface::class);

                return new PdoCurrencyCommandRepository($pdo, $queryReader);
            },

            // --- Services -----------------------------------------------

            CurrencyQueryService::class => static function (ContainerInterface $c): CurrencyQueryService {
                /** @var CurrencyQueryReaderInterface $reader */
                $reader = $c->get(CurrencyQueryReaderInterface::class);

                return new CurrencyQueryService($reader);
            },

            CurrencyCommandService::class => static function (ContainerInterface $c): CurrencyCommandService {
                /** @var CurrencyCommandRepositoryInterface $commandRepo */
                $commandRepo = $c->get(CurrencyCommandRepositoryInterface::class);

                /** @var CurrencyQueryReaderInterface $queryReader */
                $queryReader = $c->get(CurrencyQueryReaderInterface::class);

                return new CurrencyCommandService($commandRepo, $queryReader);
            },

        ]);
    }
}
