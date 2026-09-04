<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\LanguageCore\Bootstrap;

use DI\Container;
use DI\ContainerBuilder;
use PDO;
use Psr\Container\ContainerInterface;

class LanguageCoreBinding
{
    /**
     * @param ContainerBuilder<Container> $builder
     */
    public static function register(ContainerBuilder $builder): void
    {
        $builder->addDefinitions([

            \Maatify\AdminKernel\Domain\LanguageCore\LanguageWithSettingsListReaderInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new \Maatify\AdminKernel\Infrastructure\LanguageCore\PdoLanguageWithSettingsListReader($pdo);
            },

        ]);
    }
}
