<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\ContentDocuments\Bootstrap;

use DI\Container;
use DI\ContainerBuilder;
use PDO;
use Psr\Container\ContainerInterface;

class ContentDocumentBinding
{
    /**
     * @param ContainerBuilder<Container> $builder
     */
    public static function register(ContainerBuilder $builder): void
    {
        $builder->addDefinitions([

            \Maatify\AdminKernel\Domain\ContentDocuments\ContentDocumentQueryReaderInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new \Maatify\AdminKernel\Infrastructure\ContentDocuments\PDOContentDocumentQueryReaderRepository($pdo);
            },

            \Maatify\AdminKernel\Domain\ContentDocuments\ContentDocumentVersionsQueryReaderInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new \Maatify\AdminKernel\Infrastructure\ContentDocuments\PDOContentDocumentVersionsQueryReaderRepository($pdo);
            },

        ]);
    }
}
