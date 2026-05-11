<?php

declare(strict_types=1);

namespace Maatify\Settings\Bootstrap;

use Maatify\Settings\Admin\Setting\Contract\AdminSettingCommandRepositoryInterface;
use Maatify\Settings\Admin\Setting\Contract\AdminSettingQueryRepositoryInterface;
use Maatify\Settings\Admin\Setting\Infrastructure\Repository\PdoAdminSettingCommandRepository;
use Maatify\Settings\Admin\Setting\Infrastructure\Repository\PdoAdminSettingQueryRepository;
use Maatify\Settings\Admin\Setting\Service\AdminSettingService;
use Maatify\Settings\Shared\Contract\SettingValueTypeProviderInterface;
use Maatify\Settings\Shared\Infrastructure\DefaultSettingValueTypeProvider;
use Maatify\Settings\Shared\Service\SettingValueService;
use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use PDO;

final class SettingsBindings
{
    /** @param ContainerBuilder<\DI\Container> $builder */
    public static function register(ContainerBuilder $builder): void
    {
        $builder->addDefinitions([

            // ── Admin — Setting ────────────────────────────────────────

            AdminSettingQueryRepositoryInterface::class => static function (ContainerInterface $c): PdoAdminSettingQueryRepository {
                /** @var PDO $pdo */
                $pdo = $c->get(PDO::class);
                return new PdoAdminSettingQueryRepository($pdo);
            },

            AdminSettingCommandRepositoryInterface::class => static function (ContainerInterface $c): PdoAdminSettingCommandRepository {
                /** @var PDO $pdo */
                $pdo = $c->get(PDO::class);
                return new PdoAdminSettingCommandRepository($pdo);
            },

            AdminSettingService::class => static function (ContainerInterface $c): AdminSettingService {
                /** @var AdminSettingCommandRepositoryInterface $commandRepo */
                $commandRepo = $c->get(AdminSettingCommandRepositoryInterface::class);
                /** @var AdminSettingQueryRepositoryInterface $queryRepo */
                $queryRepo = $c->get(AdminSettingQueryRepositoryInterface::class);
                /** @var SettingValueTypeProviderInterface $typeProvider */
                $typeProvider = $c->get(SettingValueTypeProviderInterface::class);
                return new AdminSettingService($commandRepo, $queryRepo, $typeProvider);
            },

            // ── Shared ─────────────────────────────────────────────────

            SettingValueTypeProviderInterface::class => static function (): DefaultSettingValueTypeProvider {
                return new DefaultSettingValueTypeProvider();
            },

            SettingValueService::class => static function (ContainerInterface $c): SettingValueService {
                /** @var AdminSettingQueryRepositoryInterface $queryRepo */
                $queryRepo = $c->get(AdminSettingQueryRepositoryInterface::class);
                return new SettingValueService($queryRepo);
            },

        ]);
    }
}
