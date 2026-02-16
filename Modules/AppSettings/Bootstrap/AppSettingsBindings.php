<?php

declare(strict_types=1);

namespace Maatify\AppSettings\Bootstrap;

use DI\Container;
use DI\ContainerBuilder;
use PDO;
use Psr\Container\ContainerInterface;

/**
 * Registers all AppSettings module service bindings
 * into a DI ContainerBuilder.
 *
 * --------------------------------------------------------------------------
 * PURPOSE
 * --------------------------------------------------------------------------
 * This class acts as the Composition Root adapter for the
 * AppSettings module.
 *
 * It defines how AppSettings contracts (interfaces) are mapped
 * to their infrastructure implementations (e.g., PDO/MySQL repositories).
 *
 * --------------------------------------------------------------------------
 * DESIGN PRINCIPLES
 * --------------------------------------------------------------------------
 * - The module remains container-agnostic.
 * - No dependency on AdminKernel (or any host kernel).
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
 * - Swap the persistence layer (e.g., MySQL -> another backend)
 *
 * Example:
 *
 *   AppSettingsBindings::register($builder);
 *   $builder->addDefinitions([
 *       \Maatify\AppSettings\Repository\AppSettingsRepositoryInterface::class => CustomRepository::class,
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
final class AppSettingsBindings
{
    private const DEFAULT_WHITELIST = [
        'social' => [
            'email',
            'facebook',
            'twitter',
            'instagram',
            'linkedin',
            'youtube',
            'whatsapp',
        ],

        'apps' => [
            'android',
            'ios',
            'huawei',
            'android_agent',
            'ios_agent',
            'huawei_agent',
        ],

        'legal' => [
            'about_us',
            'privacy_policy',
            'returns_refunds_policy',
        ],

        'meta' => [
            'dev_name',
            'dev_url',
        ],

        'feature_flags' => ['*'],

        'system' => [
            'base_url',
            'environment',
            'timezone',
        ],
    ];

    /**
     * @param ContainerBuilder<Container> $builder
     */
    public static function register(ContainerBuilder $builder): void
    {
        $builder->addDefinitions([

            \Maatify\AppSettings\Policy\AppSettingsWhitelistPolicy::class => function (ContainerInterface $c) {
                return new \Maatify\AppSettings\Policy\AppSettingsWhitelistPolicy(self::DEFAULT_WHITELIST);
            },

            \Maatify\AppSettings\Repository\AppSettingsRepositoryInterface::class => function (ContainerInterface $c) {
                /** @var PDO $pdo */
                $pdo = $c->get(PDO::class);
                return new \Maatify\AppSettings\Repository\PdoAppSettingsRepository($pdo);
            },

            \Maatify\AppSettings\AppSettingsServiceInterface::class => function (ContainerInterface $c) {
                /** @var \Maatify\AppSettings\Repository\AppSettingsRepositoryInterface $repository */
                $repository = $c->get(\Maatify\AppSettings\Repository\AppSettingsRepositoryInterface::class);

                /** @var \Maatify\AppSettings\Policy\AppSettingsWhitelistPolicy $whitelistPolicy */
                $whitelistPolicy = $c->get(\Maatify\AppSettings\Policy\AppSettingsWhitelistPolicy::class);

                /** @var \Maatify\AppSettings\Policy\AppSettingsProtectionPolicy $protectionPolicy */
                $protectionPolicy = $c->get(\Maatify\AppSettings\Policy\AppSettingsProtectionPolicy::class);

                return new \Maatify\AppSettings\AppSettingsService($repository, $whitelistPolicy, $protectionPolicy);
            },

        ]);
    }
}
