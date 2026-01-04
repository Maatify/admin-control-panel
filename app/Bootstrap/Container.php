<?php

declare(strict_types=1);

namespace App\Bootstrap;

use App\Domain\Contracts\AdminEmailVerificationRepositoryInterface;
use App\Domain\Contracts\AdminIdentifierLookupInterface;
use App\Domain\Contracts\AdminPasswordRepositoryInterface;
use App\Domain\Contracts\AdminSessionRepositoryInterface;
use App\Http\Controllers\AuthController;
use App\Infrastructure\Database\PDOFactory;
use App\Infrastructure\Repository\AdminEmailRepository;
use App\Infrastructure\Repository\AdminPasswordRepository;
use App\Infrastructure\Repository\AdminRepository;
use App\Infrastructure\Repository\AdminSessionRepository;
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
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new AdminRepository($pdo);
            },
            AdminEmailRepository::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new AdminEmailRepository($pdo);
            },
            AdminEmailVerificationRepositoryInterface::class => function (ContainerInterface $c) {
                return $c->get(AdminEmailRepository::class);
            },
            AdminIdentifierLookupInterface::class => function (ContainerInterface $c) {
                return $c->get(AdminEmailRepository::class);
            },
            AdminPasswordRepositoryInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new AdminPasswordRepository($pdo);
            },
            AdminSessionRepositoryInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new AdminSessionRepository($pdo);
            },
            AuthController::class => function (ContainerInterface $c) {
                $authService = $c->get(\App\Domain\Service\AdminAuthenticationService::class);
                assert($authService instanceof \App\Domain\Service\AdminAuthenticationService);
                $blindIndexKey = $_ENV['EMAIL_BLIND_INDEX_KEY'] ?? '';
                return new AuthController($authService, $blindIndexKey);
            },
        ]);

        return $containerBuilder->build();
    }
}
