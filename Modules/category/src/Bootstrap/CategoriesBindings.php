<?php

declare(strict_types=1);

namespace Maatify\Category\Bootstrap;

use DI\Container;
use DI\ContainerBuilder;
use Maatify\Category\Contract\CategoryCommandRepositoryInterface;
use Maatify\Category\Contract\CategoryQueryReaderInterface;
use Maatify\Category\Infrastructure\Repository\PdoCategoryCommandRepository;
use Maatify\Category\Infrastructure\Repository\PdoCategoryQueryReader;
use Maatify\Category\Service\CategoryCommandService;
use Maatify\Category\Service\CategoryQueryService;
use PDO;
use Psr\Container\ContainerInterface;

/**
 * Registers all Category module service bindings
 * into a DI ContainerBuilder.
 *
 * --------------------------------------------------------------------------
 * PURPOSE
 * --------------------------------------------------------------------------
 * This class acts as the Composition Root adapter for the Category module.
 *
 * It defines how Category contracts (interfaces) are mapped to their
 * infrastructure implementations (PDO/MySQL repositories).
 *
 * --------------------------------------------------------------------------
 * DESIGN PRINCIPLES
 * --------------------------------------------------------------------------
 * - The module remains container-agnostic.
 * - No dependency on any host kernel or other module.
 * - Only relies on PDO being bound by the host application.
 * - Safe for extraction as a standalone library.
 *
 * --------------------------------------------------------------------------
 * HOST CUSTOMIZATION
 * --------------------------------------------------------------------------
 * A host application MAY override any binding after calling register():
 *
 *   CategoriesBindings::register($builder);
 *   $builder->addDefinitions([
 *       CategoryQueryReaderInterface::class => MyCustomQueryReader::class,
 *   ]);
 *
 * --------------------------------------------------------------------------
 * REQUIREMENTS
 * --------------------------------------------------------------------------
 * The host application must provide:
 * - PDO binding (pointing to the database that holds the categories tables)
 */
final class CategoriesBindings
{
    /**
     * @param ContainerBuilder<Container> $builder
     */
    public static function register(ContainerBuilder $builder): void
    {
        $builder->addDefinitions([

            // --- Infrastructure -----------------------------------------

            CategoryQueryReaderInterface::class => static function (ContainerInterface $c): PdoCategoryQueryReader {
                /** @var PDO $pdo */
                $pdo = $c->get(PDO::class);

                return new PdoCategoryQueryReader($pdo);
            },

            CategoryCommandRepositoryInterface::class => static function (ContainerInterface $c): PdoCategoryCommandRepository {
                /** @var PDO $pdo */
                $pdo = $c->get(PDO::class);

                /** @var CategoryQueryReaderInterface $queryReader */
                $queryReader = $c->get(CategoryQueryReaderInterface::class);

                return new PdoCategoryCommandRepository($pdo, $queryReader);
            },

            // --- Services -----------------------------------------------

            CategoryQueryService::class => static function (ContainerInterface $c): CategoryQueryService {
                /** @var CategoryQueryReaderInterface $reader */
                $reader = $c->get(CategoryQueryReaderInterface::class);

                return new CategoryQueryService($reader);
            },

            CategoryCommandService::class => static function (ContainerInterface $c): CategoryCommandService {
                /** @var CategoryCommandRepositoryInterface $commandRepo */
                $commandRepo = $c->get(CategoryCommandRepositoryInterface::class);

                /** @var CategoryQueryReaderInterface $queryReader */
                $queryReader = $c->get(CategoryQueryReaderInterface::class);

                return new CategoryCommandService($commandRepo, $queryReader);
            },

        ]);
    }
}

