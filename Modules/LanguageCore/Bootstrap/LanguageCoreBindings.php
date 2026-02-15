<?php

declare(strict_types=1);

namespace Maatify\LanguageCore\Bootstrap;

use DI\Container;
use DI\ContainerBuilder;
use PDO;
use Psr\Container\ContainerInterface;

/**
 * Registers all LanguageCore module service bindings
 * into a DI ContainerBuilder.
 *
 * --------------------------------------------------------------------------
 * PURPOSE
 * --------------------------------------------------------------------------
 * This class acts as the Composition Root adapter for the
 * LanguageCore module.
 *
 * It defines how module contracts (interfaces) are mapped
 * to their infrastructure implementations (MySQL repositories).
 *
 * --------------------------------------------------------------------------
 * DESIGN PRINCIPLES
 * --------------------------------------------------------------------------
 * - The module remains container-agnostic.
 * - No dependency on AdminKernel.
 * - Only relies on external contracts such as PDO.
 * - Safe for extraction as a standalone library.
 *
 * --------------------------------------------------------------------------
 * HOST CUSTOMIZATION
 * --------------------------------------------------------------------------
 * A host application MAY:
 *
 * - Override any binding after calling register()
 * - Replace repositories with custom implementations
 * - Swap MySQL storage layer
 *
 * Example:
 *
 *   LanguageCoreBindings::register($builder);
 *   $builder->addDefinitions([
 *       LanguageRepositoryInterface::class => CustomRepository::class,
 *   ]);
 *
 * --------------------------------------------------------------------------
 * IMPORTANT
 * --------------------------------------------------------------------------
 * This class contains NO business logic.
 * It is strictly responsible for dependency wiring.
 *
 * Any modification here affects module composition only.
 *
 * REQUIREMENTS:
 * The host application must provide:
 * - PDO binding
 */

final class LanguageCoreBindings
{
    /**
     * @param ContainerBuilder<Container> $builder
     */
    public static function register(ContainerBuilder $builder): void
    {
        $builder->addDefinitions([

            \Maatify\LanguageCore\Contract\LanguageRepositoryInterface::class => function (ContainerInterface $c) {
                /** @var PDO $pdo */
                $pdo = $c->get(PDO::class);
                return new \Maatify\LanguageCore\Infrastructure\Mysql\MysqlLanguageRepository($pdo);
            },

            \Maatify\LanguageCore\Contract\LanguageSettingsRepositoryInterface::class => function (ContainerInterface $c) {
                /** @var PDO $pdo */
                $pdo = $c->get(PDO::class);
                return new \Maatify\LanguageCore\Infrastructure\Mysql\MysqlLanguageSettingsRepository($pdo);
            }

        ]);
    }
}
