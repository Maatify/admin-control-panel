<?php

declare(strict_types=1);

namespace Maatify\I18n\Bootstrap;

use DI\Container;
use DI\ContainerBuilder;
use PDO;
use Psr\Container\ContainerInterface;

/**
 * Registers all I18n module service bindings
 * into a DI ContainerBuilder.
 *
 * --------------------------------------------------------------------------
 * PURPOSE
 * --------------------------------------------------------------------------
 * This class acts as the Composition Root adapter for the
 * I18n module.
 *
 * It defines how I18n contracts (interfaces) are mapped
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
 *   I18nBindings::register($builder);
 *   $builder->addDefinitions([
 *       DomainLanguageSummaryRepositoryInterface::class => CustomRepository::class,
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

final class I18nBindings
{
    /**
     * @param ContainerBuilder<Container> $builder
     */
    public static function register(ContainerBuilder $builder): void
    {
        $builder->addDefinitions([

            \Maatify\I18n\Contract\DomainLanguageSummaryRepositoryInterface::class => function (ContainerInterface $c) {
                /** @var PDO $pdo */
                $pdo = $c->get(PDO::class);
                return new \Maatify\I18n\Infrastructure\Mysql\MysqlDomainLanguageSummaryRepository($pdo);
            },

            \Maatify\I18n\Contract\DomainRepositoryInterface::class => function (ContainerInterface $c) {
                /** @var PDO $pdo */
                $pdo = $c->get(PDO::class);
                return new \Maatify\I18n\Infrastructure\Mysql\MysqlDomainRepository($pdo);
            },

            \Maatify\I18n\Contract\DomainScopeRepositoryInterface::class => function (ContainerInterface $c) {
                /** @var PDO $pdo */
                $pdo = $c->get(PDO::class);
                return new \Maatify\I18n\Infrastructure\Mysql\MysqlDomainScopeRepository($pdo);
            },

            \Maatify\I18n\Contract\I18nTransactionManagerInterface::class => function (ContainerInterface $c) {
                /** @var PDO $pdo */
                $pdo = $c->get(PDO::class);
                return new \Maatify\I18n\Infrastructure\Mysql\MysqlI18nTransactionManager($pdo);
            },

            \Maatify\I18n\Contract\KeyStatsRepositoryInterface::class => function (ContainerInterface $c) {
                /** @var PDO $pdo */
                $pdo = $c->get(PDO::class);
                return new \Maatify\I18n\Infrastructure\Mysql\MysqlKeyStatsRepository($pdo);
            },

            \Maatify\I18n\Contract\ScopeRepositoryInterface::class => function (ContainerInterface $c) {
                /** @var PDO $pdo */
                $pdo = $c->get(PDO::class);
                return new \Maatify\I18n\Infrastructure\Mysql\MysqlScopeRepository($pdo);
            },

            \Maatify\I18n\Contract\TranslationKeyRepositoryInterface::class => function (ContainerInterface $c) {
                /** @var PDO $pdo */
                $pdo = $c->get(PDO::class);
                return new \Maatify\I18n\Infrastructure\Mysql\MysqlTranslationKeyRepository($pdo);
            },

            \Maatify\I18n\Contract\TranslationRepositoryInterface::class => function (ContainerInterface $c) {
                /** @var PDO $pdo */
                $pdo = $c->get(PDO::class);
                return new \Maatify\I18n\Infrastructure\Mysql\MysqlTranslationRepository($pdo);
            }

        ]);
    }
}
