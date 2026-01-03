<?php

declare(strict_types=1);

namespace App\Bootstrap;

use App\Infrastructure\Database\PDOFactory;
use App\Infrastructure\Repository\AdminEmailRepository;
use App\Infrastructure\Repository\AdminRepository;
use DI\ContainerBuilder;
use PDO;
use Psr\Container\ContainerInterface;

class Container
{
    public static function create(): ContainerInterface
    {
        $containerBuilder = new ContainerBuilder();

        $containerBuilder->addDefinitions([
            PDO::class => function (ContainerInterface $c) {
                // Ensure environment variables are loaded before this is called
                $host = $_ENV['DB_HOST'] ?? 'localhost';
                $dbName = $_ENV['DB_NAME'] ?? 'test';
                $user = $_ENV['DB_USER'] ?? 'root';
                $pass = $_ENV['DB_PASS'] ?? '';

                $factory = new PDOFactory($host, $dbName, $user, $pass);
                return $factory->create();
            },
            AdminRepository::class => function (ContainerInterface $c) {
                return new AdminRepository($c->get(PDO::class));
            },
            AdminEmailRepository::class => function (ContainerInterface $c) {
                return new AdminEmailRepository($c->get(PDO::class));
            },
        ]);

        return $containerBuilder->build();
    }
}
