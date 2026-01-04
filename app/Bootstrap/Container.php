<?php

declare(strict_types=1);

namespace App\Bootstrap;

use App\Domain\Contracts\AdminActivityQueryInterface;
use App\Domain\Contracts\AdminEmailVerificationRepositoryInterface;
use App\Domain\Contracts\AdminIdentifierLookupInterface;
use App\Domain\Contracts\AdminNotificationChannelRepositoryInterface;
use App\Domain\Contracts\AdminNotificationPreferenceReaderInterface;
use App\Domain\Contracts\AdminNotificationPreferenceRepositoryInterface;
use App\Domain\Contracts\AdminNotificationPreferenceWriterInterface;
use App\Domain\Contracts\AdminNotificationPersistenceWriterInterface;
use App\Domain\Contracts\AdminPasswordRepositoryInterface;
use App\Domain\Contracts\AdminSessionRepositoryInterface;
use App\Domain\Contracts\AdminRoleRepositoryInterface;
use App\Domain\Contracts\AdminSessionValidationRepositoryInterface;
use App\Domain\Contracts\AuditLoggerInterface;
use App\Domain\Contracts\ClientInfoProviderInterface;
use App\Domain\Contracts\FailedNotificationRepositoryInterface;
use App\Domain\Contracts\NotificationDispatcherInterface;
use App\Domain\Contracts\NotificationReadRepositoryInterface;
use App\Domain\Contracts\RolePermissionRepositoryInterface;
use App\Domain\Contracts\SecurityEventLoggerInterface;
use App\Domain\Service\AdminAuthenticationService;
use App\Domain\Service\AdminNotificationRoutingService;
use App\Domain\Service\NotificationFailureHandler;
use App\Http\Controllers\AuthController;
use App\Infrastructure\Database\PDOFactory;
use App\Infrastructure\Repository\AdminActivityQueryRepository;
use App\Infrastructure\Repository\AdminEmailRepository;
use App\Infrastructure\Repository\AdminNotificationChannelRepository;
use App\Infrastructure\Repository\AdminNotificationPreferenceRepository;
use App\Infrastructure\Repository\AdminPasswordRepository;
use App\Infrastructure\Repository\PdoAdminNotificationPersistenceRepository;
use App\Infrastructure\Repository\PdoAdminNotificationPreferenceRepository;
use App\Infrastructure\Repository\AdminRepository;
use App\Infrastructure\Repository\AdminRoleRepository;
use App\Infrastructure\Repository\AdminSessionRepository;
use App\Infrastructure\Repository\AuditLogRepository;
use App\Infrastructure\Repository\FailedNotificationRepository;
use App\Infrastructure\Repository\NotificationReadRepository;
use App\Infrastructure\Notifications\NullNotificationDispatcher;
use App\Infrastructure\Repository\RolePermissionRepository;
use App\Domain\Service\NotificationDispatcher;
use App\Infrastructure\Notification\EmailNotificationSender;
use App\Infrastructure\Notification\FakeNotificationSender;
use App\Infrastructure\Notification\NullNotificationSender;
use App\Infrastructure\Repository\SecurityEventRepository;
use App\Infrastructure\Security\WebClientInfoProvider;
use App\Infrastructure\UX\AdminActivityMapper;
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
            AdminNotificationChannelRepositoryInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new AdminNotificationChannelRepository($pdo);
            },
            AdminNotificationPreferenceRepositoryInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new PdoAdminNotificationPreferenceRepository($pdo);
            },
            AdminNotificationPreferenceReaderInterface::class => function (ContainerInterface $c) {
                return $c->get(AdminNotificationPreferenceRepositoryInterface::class);
            },
            AdminNotificationPreferenceWriterInterface::class => function (ContainerInterface $c) {
                return $c->get(AdminNotificationPreferenceRepositoryInterface::class);
            },
            AdminNotificationPersistenceWriterInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new PdoAdminNotificationPersistenceRepository($pdo);
            },
            \App\Domain\Contracts\AdminNotificationHistoryReaderInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new \App\Infrastructure\Repository\PdoAdminNotificationHistoryReader($pdo);
            },
            \App\Domain\Contracts\AdminNotificationReadMarkerInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new \App\Infrastructure\Repository\PdoAdminNotificationReadMarker($pdo);
            },
            AdminNotificationRoutingService::class => function (ContainerInterface $c) {
                $channelRepo = $c->get(AdminNotificationChannelRepositoryInterface::class);
                $prefRepo = $c->get(AdminNotificationPreferenceRepositoryInterface::class);
                assert($channelRepo instanceof AdminNotificationChannelRepositoryInterface);
                assert($prefRepo instanceof AdminNotificationPreferenceRepositoryInterface);
                return new AdminNotificationRoutingService($channelRepo, $prefRepo);
            },
            \App\Domain\Contracts\NotificationRoutingInterface::class => function (ContainerInterface $c) {
                return $c->get(AdminNotificationRoutingService::class);
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
            AdminActivityQueryInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                $mapper = new AdminActivityMapper();
                return new AdminActivityQueryRepository($pdo, $mapper);
            },
            NotificationDispatcherInterface::class => function (ContainerInterface $c) {
                return new NullNotificationDispatcher();
            },
            FailedNotificationRepositoryInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new FailedNotificationRepository($pdo);
            },
            NotificationReadRepositoryInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new NotificationReadRepository($pdo);
            },
            NotificationFailureHandler::class => function (ContainerInterface $c) {
                $repo = $c->get(FailedNotificationRepositoryInterface::class);
                assert($repo instanceof FailedNotificationRepositoryInterface);
                return new NotificationFailureHandler($repo);
            },
            EmailNotificationSender::class => function (ContainerInterface $c) {
                return new EmailNotificationSender();
            },
            FakeNotificationSender::class => function (ContainerInterface $c) {
                return new FakeNotificationSender();
            },
            NullNotificationSender::class => function (ContainerInterface $c) {
                return new NullNotificationSender();
            },
            NotificationDispatcher::class => function (ContainerInterface $c) {
                $senders = [
                    $c->get(EmailNotificationSender::class),
                    $c->get(FakeNotificationSender::class),
                    $c->get(NullNotificationSender::class),
                ];
                /** @var iterable<mixed, \App\Domain\Contracts\NotificationSenderInterface> $senders */
                $failureHandler = $c->get(NotificationFailureHandler::class);
                assert($failureHandler instanceof NotificationFailureHandler);

                $routingService = $c->get(AdminNotificationRoutingService::class);
                assert($routingService instanceof AdminNotificationRoutingService);

                $channelRepo = $c->get(AdminNotificationChannelRepositoryInterface::class);
                assert($channelRepo instanceof AdminNotificationChannelRepositoryInterface);

                return new NotificationDispatcher(
                    $senders,
                    $failureHandler,
                    $routingService,
                    $channelRepo
                );
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
            \App\Http\Controllers\AdminNotificationPreferenceController::class => function (ContainerInterface $c) {
                $reader = $c->get(AdminNotificationPreferenceReaderInterface::class);
                $writer = $c->get(AdminNotificationPreferenceWriterInterface::class);
                assert($reader instanceof AdminNotificationPreferenceReaderInterface);
                assert($writer instanceof AdminNotificationPreferenceWriterInterface);
                return new \App\Http\Controllers\AdminNotificationPreferenceController($reader, $writer);
            },
            \App\Http\Controllers\AdminNotificationHistoryController::class => function (ContainerInterface $c) {
                $reader = $c->get(\App\Domain\Contracts\AdminNotificationHistoryReaderInterface::class);
                assert($reader instanceof \App\Domain\Contracts\AdminNotificationHistoryReaderInterface);
                return new \App\Http\Controllers\AdminNotificationHistoryController($reader);
            },
            \App\Http\Controllers\AdminNotificationReadController::class => function (ContainerInterface $c) {
                $marker = $c->get(\App\Domain\Contracts\AdminNotificationReadMarkerInterface::class);
                assert($marker instanceof \App\Domain\Contracts\AdminNotificationReadMarkerInterface);
                return new \App\Http\Controllers\AdminNotificationReadController($marker);
            },
            \App\Domain\Contracts\AdminSelfAuditReaderInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new \App\Infrastructure\Audit\PdoAdminSelfAuditReader($pdo);
            },
            \App\Domain\Contracts\AdminTargetedAuditReaderInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new \App\Infrastructure\Audit\PdoAdminTargetedAuditReader($pdo);
            },
            \App\Http\Controllers\AdminSelfAuditController::class => function (ContainerInterface $c) {
                $selfReader = $c->get(\App\Domain\Contracts\AdminSelfAuditReaderInterface::class);
                $targetedReader = $c->get(\App\Domain\Contracts\AdminTargetedAuditReaderInterface::class);
                assert($selfReader instanceof \App\Domain\Contracts\AdminSelfAuditReaderInterface);
                assert($targetedReader instanceof \App\Domain\Contracts\AdminTargetedAuditReaderInterface);
                return new \App\Http\Controllers\AdminSelfAuditController($selfReader, $targetedReader);
            },
        ]);

        return $containerBuilder->build();
    }
}
