<?php

declare(strict_types=1);

namespace App\Bootstrap;

use App\Domain\Contracts\AdminEmailVerificationRepositoryInterface;
use App\Domain\Contracts\AdminIdentifierLookupInterface;
use App\Domain\Contracts\AdminPasswordRepositoryInterface;
use App\Domain\Contracts\AdminSessionRepositoryInterface;
use App\Domain\Contracts\AdminRoleRepositoryInterface;
use App\Domain\Contracts\AdminSessionValidationRepositoryInterface;
use App\Domain\Contracts\AuditLoggerInterface;
use App\Domain\Contracts\ClientInfoProviderInterface;
use App\Domain\Contracts\RolePermissionRepositoryInterface;
use App\Domain\Contracts\SecurityEventLoggerInterface;
use App\Domain\Service\AdminAuthenticationService;
use App\Http\Controllers\AuthController;
use App\Infrastructure\Database\PDOFactory;
use App\Infrastructure\Repository\AdminEmailRepository;
use App\Infrastructure\Repository\AdminPasswordRepository;
use App\Infrastructure\Repository\AdminRepository;
use App\Infrastructure\Repository\AdminRoleRepository;
use App\Infrastructure\Repository\AdminSessionRepository;
use App\Infrastructure\Repository\AuditLogRepository;
use App\Infrastructure\Repository\RolePermissionRepository;
use App\Infrastructure\Repository\SecurityEventRepository;
use App\Infrastructure\Security\WebClientInfoProvider;
use DI\ContainerBuilder;
use Exception;
use PDO;
use Psr\Container\ContainerInterface;

class Container
{
    /**
     * @throws Exception
     */
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
            AdminSessionValidationRepositoryInterface::class => function (ContainerInterface $c) {
                return $c->get(AdminSessionRepositoryInterface::class);
            },
            AdminRoleRepositoryInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new AdminRoleRepository($pdo);
            },
            RolePermissionRepositoryInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new RolePermissionRepository($pdo);
            },
            AuditLoggerInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new AuditLogRepository($pdo);
            },
            SecurityEventLoggerInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new SecurityEventRepository($pdo);
            },
            ClientInfoProviderInterface::class => function (ContainerInterface $c) {
                return new WebClientInfoProvider();
            },
            AuthController::class => function (ContainerInterface $c) {
                $authService = $c->get(AdminAuthenticationService::class);
                assert($authService instanceof AdminAuthenticationService);
                $blindIndexKey = $_ENV['EMAIL_BLIND_INDEX_KEY'] ?? '';
                return new AuthController($authService, $blindIndexKey);
            },
        ]);

        return $containerBuilder->build();
    }
}
