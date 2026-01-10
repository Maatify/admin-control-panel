<?php

declare(strict_types=1);

namespace App\Bootstrap;

use App\Domain\Admin\Reader\AdminQueryReaderInterface;
use App\Domain\Contracts\AdminActivityQueryInterface;
use App\Domain\Contracts\AdminDirectPermissionRepositoryInterface;
use App\Domain\Contracts\AdminEmailVerificationRepositoryInterface;
use App\Domain\Contracts\AdminIdentifierLookupInterface;
use App\Domain\Contracts\AdminNotificationChannelRepositoryInterface;
use App\Domain\Contracts\AdminNotificationHistoryReaderInterface;
use App\Domain\Contracts\AdminNotificationPreferenceReaderInterface;
use App\Domain\Contracts\AdminNotificationPreferenceRepositoryInterface;
use App\Domain\Contracts\AdminNotificationPreferenceWriterInterface;
use App\Domain\Contracts\AdminNotificationPersistenceWriterInterface;
use App\Domain\Contracts\AdminNotificationReadMarkerInterface;
use App\Domain\Contracts\AdminPasswordRepositoryInterface;
use App\Domain\Contracts\AdminSecurityEventReaderInterface;
use App\Domain\Contracts\AdminSelfAuditReaderInterface;
use App\Domain\Contracts\AdminSessionRepositoryInterface;
use App\Domain\Contracts\AdminRoleRepositoryInterface;
use App\Domain\Contracts\AdminSessionValidationRepositoryInterface;
use App\Domain\Contracts\AdminTargetedAuditReaderInterface;
use App\Domain\Contracts\TelemetryAuditLoggerInterface;
use App\Domain\Contracts\RememberMeRepositoryInterface;
use App\Domain\Contracts\ClientInfoProviderInterface;
use App\Domain\Contracts\FailedNotificationRepositoryInterface;
use App\Domain\Contracts\NotificationDispatcherInterface;
use App\Domain\Contracts\NotificationReadRepositoryInterface;
use App\Domain\Contracts\NotificationRoutingInterface;
use App\Domain\Contracts\NotificationSenderInterface;
use App\Domain\Contracts\RolePermissionRepositoryInterface;
use App\Domain\Contracts\RoleRepositoryInterface;
use App\Domain\Contracts\SecurityEventLoggerInterface;
use App\Domain\Contracts\StepUpGrantRepositoryInterface;
use App\Domain\Contracts\TotpSecretRepositoryInterface;
use App\Domain\Contracts\TotpServiceInterface;
use App\Domain\Contracts\VerificationCodeGeneratorInterface;
use App\Domain\Contracts\VerificationCodePolicyResolverInterface;
use App\Domain\Contracts\VerificationCodeRepositoryInterface;
use App\Domain\Contracts\VerificationCodeValidatorInterface;
use App\Domain\DTO\AdminConfigDTO;
use App\Domain\Ownership\SystemOwnershipRepositoryInterface;
use App\Domain\Service\AdminAuthenticationService;
use App\Domain\Service\AdminEmailVerificationService;
use App\Domain\Service\AdminNotificationRoutingService;
use App\Domain\Service\NotificationFailureHandler;
use App\Domain\Service\RecoveryStateService;
use App\Domain\Service\RememberMeService;
use App\Domain\Service\RoleAssignmentService;
use App\Domain\Service\RoleHierarchyComparator;
use App\Domain\Service\RoleLevelResolver;
use App\Domain\Service\StepUpService;
use App\Domain\Service\VerificationCodeGenerator;
use App\Domain\Service\VerificationCodePolicyResolver;
use App\Domain\Service\VerificationCodeValidator;
use App\Domain\Service\SessionRevocationService;
use App\Domain\Service\AuthorizationService;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminNotificationHistoryController;
use App\Http\Controllers\AdminNotificationPreferenceController;
use App\Http\Controllers\AdminNotificationReadController;
use App\Http\Controllers\AdminSecurityEventController;
use App\Http\Controllers\AdminSelfAuditController;
use App\Http\Controllers\AdminTargetedAuditController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\StepUpController;
use App\Http\Controllers\NotificationQueryController;
use App\Http\Controllers\AdminEmailVerificationController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\EmailVerificationController;
use App\Http\Controllers\Web\LoginController;
use App\Http\Controllers\Web\LogoutController;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Controllers\Web\TelegramConnectController;
use App\Http\Controllers\Api\SessionQueryController;
use App\Http\Controllers\Api\SessionRevokeController;
use App\Http\Controllers\Api\SessionBulkRevokeController;
use App\Http\Controllers\Web\TwoFactorController;
use App\Http\Controllers\Ui\UiAdminsController;
use App\Http\Controllers\Ui\UiDashboardController;
use App\Http\Controllers\Ui\UiPermissionsController;
use App\Http\Controllers\Ui\UiRolesController;
use App\Http\Controllers\Ui\UiSettingsController;
use App\Http\Controllers\Ui\SessionListController;
use App\Domain\Session\Reader\SessionListReaderInterface;
use App\Infrastructure\Reader\Session\PdoSessionListReader;
use App\Http\Middleware\RememberMeMiddleware;
use App\Http\Middleware\ScopeGuardMiddleware;
use App\Http\Middleware\SessionStateGuardMiddleware;
use App\Domain\Contracts\AuthoritativeSecurityAuditWriterInterface;
use App\Infrastructure\Audit\PdoAdminSecurityEventReader;
use App\Infrastructure\Audit\PdoAdminSelfAuditReader;
use App\Infrastructure\Audit\PdoAdminTargetedAuditReader;
use App\Infrastructure\Audit\PdoAuthoritativeAuditWriter;
use App\Infrastructure\Database\PDOFactory;
use App\Infrastructure\Notification\TelegramHandler;
use App\Infrastructure\Repository\AdminActivityQueryRepository;
use App\Infrastructure\Repository\AdminEmailRepository;
use App\Infrastructure\Repository\AdminNotificationChannelRepository;
use App\Infrastructure\Repository\AdminNotificationPreferenceRepository;
use App\Infrastructure\Repository\AdminPasswordRepository;
use App\Infrastructure\Repository\FileTotpSecretRepository;
use App\Infrastructure\Repository\PdoAdminNotificationHistoryReader;
use App\Infrastructure\Repository\PdoAdminNotificationPersistenceRepository;
use App\Infrastructure\Repository\PdoAdminNotificationPreferenceRepository;
use App\Infrastructure\Repository\PdoRememberMeRepository;
use App\Infrastructure\Repository\AdminRepository;
use App\Infrastructure\Repository\PdoAdminDirectPermissionRepository;
use App\Infrastructure\Repository\AdminRoleRepository;
use App\Infrastructure\Repository\AdminSessionRepository;
use App\Infrastructure\Audit\PdoTelemetryAuditLogger;
use App\Infrastructure\Repository\FailedNotificationRepository;
use App\Infrastructure\Repository\NotificationReadRepository;
use App\Infrastructure\Notifications\NullNotificationDispatcher;
use App\Infrastructure\Repository\PdoAdminNotificationReadMarker;
use App\Infrastructure\Repository\PdoRoleRepository;
use App\Infrastructure\Repository\PdoStepUpGrantRepository;
use App\Infrastructure\Repository\PdoSystemOwnershipRepository;
use App\Infrastructure\Repository\PdoVerificationCodeRepository;
use App\Infrastructure\Repository\RedisStepUpGrantRepository;
use App\Infrastructure\Repository\RolePermissionRepository;
use App\Domain\Service\NotificationDispatcher;
use App\Domain\Service\PasswordService;
use App\Infrastructure\Notification\EmailNotificationSender;
use App\Infrastructure\Notification\FakeNotificationSender;
use App\Infrastructure\Notification\NullNotificationSender;
use App\Infrastructure\Repository\SecurityEventRepository;
use App\Infrastructure\Security\WebClientInfoProvider;
use App\Infrastructure\Service\Google2faTotpService;
use App\Infrastructure\UX\AdminActivityMapper;
use App\Modules\Crypto\KeyRotation\KeyRotationService;
use App\Modules\Crypto\KeyRotation\Providers\InMemoryKeyProvider;
use App\Modules\Crypto\KeyRotation\Policy\StrictSingleActiveKeyPolicy;
use App\Modules\Crypto\KeyRotation\DTO\CryptoKeyDTO;
use App\Modules\Crypto\KeyRotation\KeyStatusEnum;
use App\Modules\Crypto\HKDF\HKDFService;
use App\Modules\Crypto\Reversible\Registry\ReversibleCryptoAlgorithmRegistry;
use App\Modules\Crypto\Reversible\Algorithms\Aes256GcmAlgorithm;
use App\Modules\Crypto\DX\CryptoDirectFactory;
use App\Modules\Crypto\DX\CryptoContextFactory;
use App\Modules\Crypto\DX\CryptoProvider;
use App\Modules\InputNormalization\Contracts\InputNormalizerInterface;
use App\Modules\InputNormalization\Middleware\InputNormalizationMiddleware;
use App\Modules\InputNormalization\Normalizer\InputNormalizer;
use App\Modules\Validation\Contracts\ValidatorInterface;
use App\Modules\Validation\Guard\ValidationGuard;
use App\Modules\Validation\Validator\RespectValidator;
use DI\ContainerBuilder;
use Exception;
use PDO;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;
use Psr\Log\AbstractLogger;
use Dotenv\Dotenv;
use App\Http\Middleware\RecoveryStateMiddleware;
use App\Infrastructure\Reader\Admin\PdoAdminQueryReader;

class Container
{
    /**
     * @throws Exception
     */
    public static function create(): ContainerInterface
    {
        $containerBuilder = new ContainerBuilder();

        // Load ENV
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
        $dotenv->safeLoad();
        $dotenv->required([
            'APP_ENV',
            'APP_DEBUG',
            'DB_HOST',
            'DB_NAME',
            'DB_USER',
            'DB_PASS',
            'PASSWORD_PEPPER',
            'EMAIL_BLIND_INDEX_KEY',
            'APP_TIMEZONE',
            'EMAIL_ENCRYPTION_KEY'
        ])->notEmpty();

        // Create Config DTO
        $config = new AdminConfigDTO(
            appEnv: $_ENV['APP_ENV'],
            appDebug: $_ENV['APP_DEBUG'] === 'true',
            timezone: $_ENV['APP_TIMEZONE'],
            passwordPepper: $_ENV['PASSWORD_PEPPER'],
            passwordPepperOld: $_ENV['PASSWORD_PEPPER_OLD'] ?? null,
            emailBlindIndexKey: $_ENV['EMAIL_BLIND_INDEX_KEY'],
            emailEncryptionKey: $_ENV['EMAIL_ENCRYPTION_KEY'],
            dbHost: $_ENV['DB_HOST'],
            dbName: $_ENV['DB_NAME'],
            dbUser: $_ENV['DB_USER'],
            dbPass: $_ENV['DB_PASS'],
            isRecoveryMode: ($_ENV['RECOVERY_MODE'] ?? 'false') === 'true'
        );

        // Enforce Timezone
        date_default_timezone_set($config->timezone);

        $containerBuilder->addDefinitions([
            AdminConfigDTO::class => function () use ($config) {
                return $config;
            },
            ValidatorInterface::class => function (ContainerInterface $c) {
                return new RespectValidator();
            },
            ValidationGuard::class => function (ContainerInterface $c) {
                $validator = $c->get(ValidatorInterface::class);
                assert($validator instanceof ValidatorInterface);
                return new ValidationGuard($validator);
            },
            InputNormalizerInterface::class => function (ContainerInterface $c) {
                return new InputNormalizer();
            },
            InputNormalizationMiddleware::class => function (ContainerInterface $c) {
                $normalizer = $c->get(InputNormalizerInterface::class);
                assert($normalizer instanceof InputNormalizerInterface);
                return new InputNormalizationMiddleware($normalizer);
            },
            AuthorizationService::class => function (ContainerInterface $c) {
                $adminRoleRepo = $c->get(AdminRoleRepositoryInterface::class);
                $rolePermissionRepo = $c->get(RolePermissionRepositoryInterface::class);
                $directPermissionRepo = $c->get(AdminDirectPermissionRepositoryInterface::class);
                $auditLogger = $c->get(TelemetryAuditLoggerInterface::class);
                $securityLogger = $c->get(SecurityEventLoggerInterface::class);
                $clientInfo = $c->get(ClientInfoProviderInterface::class);
                $ownershipRepo = $c->get(SystemOwnershipRepositoryInterface::class);

                assert($adminRoleRepo instanceof AdminRoleRepositoryInterface);
                assert($rolePermissionRepo instanceof RolePermissionRepositoryInterface);
                assert($directPermissionRepo instanceof AdminDirectPermissionRepositoryInterface);
                assert($auditLogger instanceof TelemetryAuditLoggerInterface);
                assert($securityLogger instanceof SecurityEventLoggerInterface);
                assert($clientInfo instanceof ClientInfoProviderInterface);
                assert($ownershipRepo instanceof SystemOwnershipRepositoryInterface);

                return new AuthorizationService(
                    $adminRoleRepo,
                    $rolePermissionRepo,
                    $directPermissionRepo,
                    $auditLogger,
                    $securityLogger,
                    $clientInfo,
                    $ownershipRepo
                );
            },
            Twig::class => function (ContainerInterface $c) {
                return Twig::create(__DIR__ . '/../../templates', ['cache' => false]);
            },
            PDO::class => function (ContainerInterface $c) {
                $config = $c->get(AdminConfigDTO::class);
                assert($config instanceof AdminConfigDTO);

                $factory = new PDOFactory(
                    $config->dbHost,
                    $config->dbName,
                    $config->dbUser,
                    $config->dbPass
                );
                return $factory->create();
            },
            AdminRepository::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new AdminRepository($pdo);
            },
            AdminController::class => function (ContainerInterface $c) {
                $adminRepo = $c->get(AdminRepository::class);
                $emailRepo = $c->get(AdminEmailRepository::class);
                $config = $c->get(AdminConfigDTO::class);
                $validationGuard = $c->get(ValidationGuard::class);

                assert($adminRepo instanceof AdminRepository);
                assert($emailRepo instanceof AdminEmailRepository);
                assert($config instanceof AdminConfigDTO);
                assert($validationGuard instanceof ValidationGuard);

                return new AdminController($adminRepo, $emailRepo, $config, $validationGuard);
            },
            AdminEmailRepository::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new AdminEmailRepository($pdo);
            },
            AdminEmailVerificationRepositoryInterface::class => function (ContainerInterface $c) {
                return $c->get(AdminEmailRepository::class);
            },
            AdminEmailVerificationService::class => function (ContainerInterface $c) {
                $repo = $c->get(AdminEmailVerificationRepositoryInterface::class);
                $auditWriter = $c->get(AuthoritativeSecurityAuditWriterInterface::class);
                $clientInfo = $c->get(ClientInfoProviderInterface::class);
                $pdo = $c->get(PDO::class);

                assert($repo instanceof AdminEmailVerificationRepositoryInterface);
                assert($auditWriter instanceof AuthoritativeSecurityAuditWriterInterface);
                assert($clientInfo instanceof ClientInfoProviderInterface);
                assert($pdo instanceof PDO);

                return new AdminEmailVerificationService($repo, $auditWriter, $clientInfo, $pdo);
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
            AdminNotificationHistoryReaderInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new PdoAdminNotificationHistoryReader($pdo);
            },
            AdminNotificationReadMarkerInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new PdoAdminNotificationReadMarker($pdo);
            },
            AdminNotificationRoutingService::class => function (ContainerInterface $c) {
                $channelRepo = $c->get(AdminNotificationChannelRepositoryInterface::class);
                $prefRepo = $c->get(AdminNotificationPreferenceRepositoryInterface::class);
                assert($channelRepo instanceof AdminNotificationChannelRepositoryInterface);
                assert($prefRepo instanceof AdminNotificationPreferenceRepositoryInterface);
                return new AdminNotificationRoutingService($channelRepo, $prefRepo);
            },
            NotificationRoutingInterface::class => function (ContainerInterface $c) {
                return $c->get(AdminNotificationRoutingService::class);
            },
            AdminPasswordRepositoryInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new AdminPasswordRepository($pdo);
            },
            PasswordService::class => function (ContainerInterface $c) {
                $config = $c->get(AdminConfigDTO::class);
                assert($config instanceof AdminConfigDTO);

                return new PasswordService($config->passwordPepper, $config->passwordPepperOld);
            },
            AdminAuthenticationService::class => function (ContainerInterface $c) {
                $lookup = $c->get(AdminIdentifierLookupInterface::class);
                $verificationRepo = $c->get(AdminEmailVerificationRepositoryInterface::class);
                $passwordRepo = $c->get(AdminPasswordRepositoryInterface::class);
                $sessionRepo = $c->get(AdminSessionRepositoryInterface::class);
                $auditLogger = $c->get(TelemetryAuditLoggerInterface::class);
                $securityLogger = $c->get(SecurityEventLoggerInterface::class);
                $clientInfo = $c->get(ClientInfoProviderInterface::class);
                $outboxWriter = $c->get(AuthoritativeSecurityAuditWriterInterface::class);
                $recoveryState = $c->get(RecoveryStateService::class);
                $pdo = $c->get(PDO::class);
                $passwordService = $c->get(PasswordService::class);

                assert($lookup instanceof AdminIdentifierLookupInterface);
                assert($verificationRepo instanceof AdminEmailVerificationRepositoryInterface);
                assert($passwordRepo instanceof AdminPasswordRepositoryInterface);
                assert($sessionRepo instanceof AdminSessionRepositoryInterface);
                assert($auditLogger instanceof TelemetryAuditLoggerInterface);
                assert($securityLogger instanceof SecurityEventLoggerInterface);
                assert($clientInfo instanceof ClientInfoProviderInterface);
                assert($outboxWriter instanceof AuthoritativeSecurityAuditWriterInterface);
                assert($recoveryState instanceof RecoveryStateService);
                assert($pdo instanceof PDO);
                assert($passwordService instanceof PasswordService);

                return new AdminAuthenticationService(
                    $lookup,
                    $verificationRepo,
                    $passwordRepo,
                    $sessionRepo,
                    $auditLogger,
                    $securityLogger,
                    $clientInfo,
                    $outboxWriter,
                    $recoveryState,
                    $pdo,
                    $passwordService
                );
            },
            AdminSessionRepositoryInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new AdminSessionRepository($pdo);
            },
            AdminSessionValidationRepositoryInterface::class => function (ContainerInterface $c) {
                return $c->get(AdminSessionRepositoryInterface::class);
            },
            SessionRevocationService::class => function (ContainerInterface $c) {
                $repo = $c->get(AdminSessionValidationRepositoryInterface::class);
                $auditWriter = $c->get(AuthoritativeSecurityAuditWriterInterface::class);
                $clientInfo = $c->get(ClientInfoProviderInterface::class);
                $pdo = $c->get(PDO::class);

                assert($repo instanceof AdminSessionValidationRepositoryInterface);
                assert($auditWriter instanceof AuthoritativeSecurityAuditWriterInterface);
                assert($clientInfo instanceof ClientInfoProviderInterface);
                assert($pdo instanceof PDO);

                return new SessionRevocationService($repo, $auditWriter, $clientInfo, $pdo);
            },
            RememberMeRepositoryInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new PdoRememberMeRepository($pdo);
            },
            AdminRoleRepositoryInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new AdminRoleRepository($pdo);
            },
            AdminDirectPermissionRepositoryInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new PdoAdminDirectPermissionRepository($pdo);
            },
            RolePermissionRepositoryInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new RolePermissionRepository($pdo);
            },
            SystemOwnershipRepositoryInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new PdoSystemOwnershipRepository($pdo);
            },
            RoleRepositoryInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new PdoRoleRepository($pdo);
            },
            RoleLevelResolver::class => function () {
                return new RoleLevelResolver();
            },
            RoleHierarchyComparator::class => function (ContainerInterface $c) {
                $adminRoleRepo = $c->get(AdminRoleRepositoryInterface::class);
                $roleRepo = $c->get(RoleRepositoryInterface::class);
                $resolver = $c->get(RoleLevelResolver::class);

                assert($adminRoleRepo instanceof AdminRoleRepositoryInterface);
                assert($roleRepo instanceof RoleRepositoryInterface);
                assert($resolver instanceof RoleLevelResolver);

                return new RoleHierarchyComparator($adminRoleRepo, $roleRepo, $resolver);
            },
            RoleAssignmentService::class => function (ContainerInterface $c) {
                $recoveryState = $c->get(RecoveryStateService::class);
                $stepUpService = $c->get(StepUpService::class);
                $grantRepo = $c->get(StepUpGrantRepositoryInterface::class);
                $hierarchyComparator = $c->get(RoleHierarchyComparator::class);
                $adminRoleRepo = $c->get(AdminRoleRepositoryInterface::class);
                $auditWriter = $c->get(AuthoritativeSecurityAuditWriterInterface::class);
                $clientInfo = $c->get(ClientInfoProviderInterface::class);
                $pdo = $c->get(PDO::class);

                assert($recoveryState instanceof RecoveryStateService);
                assert($stepUpService instanceof StepUpService);
                assert($grantRepo instanceof StepUpGrantRepositoryInterface);
                assert($hierarchyComparator instanceof RoleHierarchyComparator);
                assert($adminRoleRepo instanceof AdminRoleRepositoryInterface);
                assert($auditWriter instanceof AuthoritativeSecurityAuditWriterInterface);
                assert($clientInfo instanceof ClientInfoProviderInterface);
                assert($pdo instanceof PDO);

                return new RoleAssignmentService(
                    $recoveryState,
                    $stepUpService,
                    $grantRepo,
                    $hierarchyComparator,
                    $adminRoleRepo,
                    $auditWriter,
                    $clientInfo,
                    $pdo
                );
            },
            LoggerInterface::class => function () {
                return new class extends AbstractLogger {
                    public function log(
                        mixed $level,
                        string|\Stringable $message,
                        array $context = []
                    ): void {
                        $levelStr = is_scalar($level) || $level instanceof \Stringable
                            ? (string) $level
                            : 'mixed';

                        $jsonContext = $context !== []
                            ? json_encode(
                                $context,
                                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                            )
                            : '';

                        if ($jsonContext === false) {
                            $jsonContext = '{"error":"json_encode_failed"}';
                        }

                        error_log(sprintf(
                            '[%s] %s %s',
                            strtoupper($levelStr),
                            (string) $message,
                            $jsonContext
                        ));
                    }
                };
            },
            TelemetryAuditLoggerInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new PdoTelemetryAuditLogger($pdo);
            },
            AuthoritativeSecurityAuditWriterInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new PdoAuthoritativeAuditWriter($pdo);
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
                /** @var iterable<mixed, NotificationSenderInterface> $senders */
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
                $config = $c->get(AdminConfigDTO::class);
                $validationGuard = $c->get(ValidationGuard::class);
                assert($authService instanceof AdminAuthenticationService);
                assert($config instanceof AdminConfigDTO);
                assert($validationGuard instanceof ValidationGuard);

                return new AuthController($authService, $config->emailBlindIndexKey, $validationGuard);
            },
            LoginController::class => function (ContainerInterface $c) {
                $authService = $c->get(AdminAuthenticationService::class);
                $sessionRepo = $c->get(AdminSessionValidationRepositoryInterface::class);
                $rememberMeService = $c->get(RememberMeService::class);
                $view = $c->get(Twig::class);
                $config = $c->get(AdminConfigDTO::class);

                assert($authService instanceof AdminAuthenticationService);
                assert($sessionRepo instanceof AdminSessionValidationRepositoryInterface);
                assert($rememberMeService instanceof RememberMeService);
                assert($view instanceof Twig);
                assert($config instanceof AdminConfigDTO);

                return new LoginController(
                    $authService,
                    $sessionRepo,
                    $rememberMeService,
                    $config->emailBlindIndexKey,
                    $view
                );
            },
            LogoutController::class => function (ContainerInterface $c) {
                $sessionRepo = $c->get(AdminSessionValidationRepositoryInterface::class);
                $rememberMeService = $c->get(RememberMeService::class);
                $logger = $c->get(SecurityEventLoggerInterface::class);
                $clientInfo = $c->get(ClientInfoProviderInterface::class);
                $authService = $c->get(AdminAuthenticationService::class);

                assert($sessionRepo instanceof AdminSessionValidationRepositoryInterface);
                assert($rememberMeService instanceof RememberMeService);
                assert($logger instanceof SecurityEventLoggerInterface);
                assert($clientInfo instanceof ClientInfoProviderInterface);
                assert($authService instanceof AdminAuthenticationService);

                return new LogoutController(
                    $sessionRepo,
                    $rememberMeService,
                    $logger,
                    $clientInfo,
                    $authService
                );
            },
            EmailVerificationController::class => function (ContainerInterface $c) {
                $validator = $c->get(VerificationCodeValidatorInterface::class);
                $generator = $c->get(VerificationCodeGeneratorInterface::class);
                $verificationService = $c->get(AdminEmailVerificationService::class);
                $lookup = $c->get(AdminIdentifierLookupInterface::class);
                $view = $c->get(Twig::class);
                $logger = $c->get(LoggerInterface::class);
                $config = $c->get(AdminConfigDTO::class);

                assert($validator instanceof VerificationCodeValidatorInterface);
                assert($generator instanceof VerificationCodeGeneratorInterface);
                assert($verificationService instanceof AdminEmailVerificationService);
                assert($lookup instanceof AdminIdentifierLookupInterface);
                assert($view instanceof Twig);
                assert($logger instanceof LoggerInterface);
                assert($config instanceof AdminConfigDTO);

                return new EmailVerificationController(
                    $validator,
                    $generator,
                    $verificationService,
                    $lookup,
                    $view,
                    $logger,
                    $config->emailBlindIndexKey
                );
            },
            TelegramConnectController::class => function (ContainerInterface $c) {
                $generator = $c->get(VerificationCodeGeneratorInterface::class);
                $view = $c->get(Twig::class);
                assert($generator instanceof VerificationCodeGeneratorInterface);
                assert($view instanceof Twig);
                return new TelegramConnectController($generator, $view);
            },
            TelegramWebhookController::class => function (ContainerInterface $c) {
                $handler = $c->get(TelegramHandler::class);
                $logger = $c->get(LoggerInterface::class);
                $validationGuard = $c->get(ValidationGuard::class);
                assert($handler instanceof TelegramHandler);
                assert($logger instanceof LoggerInterface);
                assert($validationGuard instanceof ValidationGuard);
                return new TelegramWebhookController($handler, $logger, $validationGuard);
            },
            UiAdminsController::class => function (ContainerInterface $c) {
                $view = $c->get(Twig::class);
                assert($view instanceof Twig);
                return new UiAdminsController($view);
            },
            UiDashboardController::class => function (ContainerInterface $c) {
                $webDashboard = $c->get(DashboardController::class);
                assert($webDashboard instanceof DashboardController);
                return new UiDashboardController($webDashboard);
            },
            UiPermissionsController::class => function (ContainerInterface $c) {
                $view = $c->get(Twig::class);
                assert($view instanceof Twig);
                return new UiPermissionsController($view);
            },
            UiRolesController::class => function (ContainerInterface $c) {
                $view = $c->get(Twig::class);
                assert($view instanceof Twig);
                return new UiRolesController($view);
            },
            UiSettingsController::class => function (ContainerInterface $c) {
                $view = $c->get(Twig::class);
                assert($view instanceof Twig);
                return new UiSettingsController($view);
            },
            TelegramHandler::class => function (ContainerInterface $c) {
                $validator = $c->get(VerificationCodeValidatorInterface::class);
                $repo = $c->get(AdminNotificationChannelRepositoryInterface::class);
                $logger = $c->get(LoggerInterface::class);
                assert($validator instanceof VerificationCodeValidatorInterface);
                assert($repo instanceof AdminNotificationChannelRepositoryInterface);
                assert($logger instanceof LoggerInterface);
                return new TelegramHandler($validator, $repo, $logger);
            },
            DashboardController::class => function (ContainerInterface $c) {
                $view = $c->get(Twig::class);
                assert($view instanceof Twig);
                return new DashboardController($view);
            },
            TwoFactorController::class => function (ContainerInterface $c) {
                $stepUp = $c->get(StepUpService::class);
                $totp = $c->get(TotpServiceInterface::class);
                $view = $c->get(Twig::class);
                assert($stepUp instanceof StepUpService);
                assert($totp instanceof TotpServiceInterface);
                assert($view instanceof Twig);
                return new TwoFactorController($stepUp, $totp, $view);
            },
            AdminNotificationPreferenceController::class => function (ContainerInterface $c) {
                $reader = $c->get(AdminNotificationPreferenceReaderInterface::class);
                $writer = $c->get(AdminNotificationPreferenceWriterInterface::class);
                $validationGuard = $c->get(ValidationGuard::class);
                assert($reader instanceof AdminNotificationPreferenceReaderInterface);
                assert($writer instanceof AdminNotificationPreferenceWriterInterface);
                assert($validationGuard instanceof ValidationGuard);
                return new AdminNotificationPreferenceController($reader, $writer, $validationGuard);
            },
            AdminNotificationHistoryController::class => function (ContainerInterface $c) {
                $reader = $c->get(AdminNotificationHistoryReaderInterface::class);
                $validationGuard = $c->get(ValidationGuard::class);
                assert($reader instanceof AdminNotificationHistoryReaderInterface);
                assert($validationGuard instanceof ValidationGuard);
                return new AdminNotificationHistoryController($reader, $validationGuard);
            },
            AdminNotificationReadController::class => function (ContainerInterface $c) {
                $marker = $c->get(AdminNotificationReadMarkerInterface::class);
                $validationGuard = $c->get(ValidationGuard::class);
                assert($marker instanceof AdminNotificationReadMarkerInterface);
                assert($validationGuard instanceof ValidationGuard);
                return new AdminNotificationReadController($marker, $validationGuard);
            },
            NotificationQueryController::class => function (ContainerInterface $c) {
                $repository = $c->get(NotificationReadRepositoryInterface::class);
                $validationGuard = $c->get(ValidationGuard::class);
                assert($repository instanceof NotificationReadRepositoryInterface);
                assert($validationGuard instanceof ValidationGuard);
                return new NotificationQueryController($repository, $validationGuard);
            },
            AdminEmailVerificationController::class => function (ContainerInterface $c) {
                $service = $c->get(AdminEmailVerificationService::class);
                $repo = $c->get(AdminEmailRepository::class);
                $validationGuard = $c->get(ValidationGuard::class);
                assert($service instanceof AdminEmailVerificationService);
                assert($repo instanceof AdminEmailRepository);
                assert($validationGuard instanceof ValidationGuard);
                return new AdminEmailVerificationController($service, $repo, $validationGuard);
            },
            AdminSelfAuditReaderInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new PdoAdminSelfAuditReader($pdo);
            },
            AdminTargetedAuditReaderInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new PdoAdminTargetedAuditReader($pdo);
            },
            AdminSecurityEventReaderInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new PdoAdminSecurityEventReader($pdo);
            },
            AdminSelfAuditController::class => function (ContainerInterface $c) {
                $selfReader = $c->get(AdminSelfAuditReaderInterface::class);
                assert($selfReader instanceof AdminSelfAuditReaderInterface);
                return new AdminSelfAuditController($selfReader);
            },
            AdminTargetedAuditController::class => function (ContainerInterface $c) {
                $targetedReader = $c->get(AdminTargetedAuditReaderInterface::class);
                assert($targetedReader instanceof AdminTargetedAuditReaderInterface);
                return new AdminTargetedAuditController($targetedReader);
            },
            AdminSecurityEventController::class => function (ContainerInterface $c) {
                $securityReader = $c->get(AdminSecurityEventReaderInterface::class);
                assert($securityReader instanceof AdminSecurityEventReaderInterface);
                return new AdminSecurityEventController($securityReader);
            },

            // Phase 14.3: Sessions
            SessionListReaderInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                $config = $c->get(AdminConfigDTO::class);
                assert($pdo instanceof PDO);
                assert($config instanceof AdminConfigDTO);
                return new PdoSessionListReader($pdo, $config);
            },
            SessionListController::class => function (ContainerInterface $c) {
                $twig = $c->get(Twig::class);
                assert($twig instanceof Twig);
                return new SessionListController($twig);
            },
            SessionQueryController::class => function (ContainerInterface $c) {
                $reader = $c->get(SessionListReaderInterface::class);
                $auth = $c->get(AuthorizationService::class);
                $validationGuard = $c->get(ValidationGuard::class);
                $filterResolver = $c->get(\App\Infrastructure\Query\ListFilterResolver::class);

                assert($reader instanceof SessionListReaderInterface);
                assert($auth instanceof AuthorizationService);
                assert($validationGuard instanceof ValidationGuard);
                assert($filterResolver instanceof \App\Infrastructure\Query\ListFilterResolver);

                return new SessionQueryController($reader, $auth, $validationGuard, $filterResolver);
            },
            SessionRevokeController::class => function (ContainerInterface $c) {
                $service = $c->get(SessionRevocationService::class);
                $auth = $c->get(AuthorizationService::class);
                $validationGuard = $c->get(ValidationGuard::class);
                assert($service instanceof SessionRevocationService);
                assert($auth instanceof AuthorizationService);
                assert($validationGuard instanceof ValidationGuard);
                return new SessionRevokeController($service, $auth, $validationGuard);
            },
            SessionBulkRevokeController::class => function (ContainerInterface $c) {
                $service = $c->get(SessionRevocationService::class);
                $auth = $c->get(AuthorizationService::class);
                $validationGuard = $c->get(ValidationGuard::class);
                assert($service instanceof SessionRevocationService);
                assert($auth instanceof AuthorizationService);
                assert($validationGuard instanceof ValidationGuard);
                return new SessionBulkRevokeController($service, $auth, $validationGuard);
            },

            // Admin List
//            AdminListReaderInterface::class => function (ContainerInterface $c) {
//                $pdo = $c->get(PDO::class);
//                $config = $c->get(AdminConfigDTO::class);
//                assert($pdo instanceof PDO);
//                assert($config instanceof AdminConfigDTO);
//                return new PdoAdminListReader($pdo, $config);
//            },
//            AdminListController::class => function (ContainerInterface $c) {
//                $reader = $c->get(AdminListReaderInterface::class);
//                $validationGuard = $c->get(ValidationGuard::class);
//                assert($reader instanceof AdminListReaderInterface);
//                assert($validationGuard instanceof ValidationGuard);
//                return new AdminListController($reader, $validationGuard);
//            },
            AdminQueryReaderInterface::class =>
                function ($c): AdminQueryReaderInterface {
                $pdo = $c->get(PDO::class);
                $config = $c->get(AdminConfigDTO::class);

                assert($pdo instanceof PDO);
                assert($config instanceof AdminConfigDTO);

                return new PdoAdminQueryReader(
                    $pdo,
                    $config
                );
            },

            // Phase 12
            StepUpGrantRepositoryInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new PdoStepUpGrantRepository($pdo);
            },
            TotpSecretRepositoryInterface::class => function (ContainerInterface $c) {
                $storagePath = __DIR__ . '/../../storage/totp';
                return new FileTotpSecretRepository($storagePath);
            },
            TotpServiceInterface::class => function (ContainerInterface $c) {
                return new Google2faTotpService();
            },
            StepUpService::class => function (ContainerInterface $c) {
                 $grantRepo = $c->get(StepUpGrantRepositoryInterface::class);
                 $secretRepo = $c->get(TotpSecretRepositoryInterface::class);
                 $totpService = $c->get(TotpServiceInterface::class);
                 $auditLogger = $c->get(TelemetryAuditLoggerInterface::class);
                 $outboxWriter = $c->get(AuthoritativeSecurityAuditWriterInterface::class);
                 $clientInfo = $c->get(ClientInfoProviderInterface::class);
                 $recoveryState = $c->get(RecoveryStateService::class);
                 $pdo = $c->get(PDO::class);

                 assert($grantRepo instanceof StepUpGrantRepositoryInterface);
                 assert($secretRepo instanceof TotpSecretRepositoryInterface);
                 assert($totpService instanceof TotpServiceInterface);
                 assert($auditLogger instanceof TelemetryAuditLoggerInterface);
                 assert($outboxWriter instanceof AuthoritativeSecurityAuditWriterInterface);
                 assert($clientInfo instanceof ClientInfoProviderInterface);
                 assert($recoveryState instanceof RecoveryStateService);
                 assert($pdo instanceof PDO);

                 return new StepUpService(
                     $grantRepo,
                     $secretRepo,
                     $totpService,
                     $auditLogger,
                     $outboxWriter,
                     $clientInfo,
                     $recoveryState,
                     $pdo
                 );
            },
            SessionStateGuardMiddleware::class => function (ContainerInterface $c) {
                $service = $c->get(StepUpService::class);
                $repo = $c->get(TotpSecretRepositoryInterface::class);
                assert($service instanceof StepUpService);
                assert($repo instanceof TotpSecretRepositoryInterface);
                return new SessionStateGuardMiddleware($service, $repo);
            },
            ScopeGuardMiddleware::class => function (ContainerInterface $c) {
                $service = $c->get(StepUpService::class);
                assert($service instanceof StepUpService);
                return new ScopeGuardMiddleware($service);
            },
            StepUpController::class => function (ContainerInterface $c) {
                $service = $c->get(StepUpService::class);
                $validationGuard = $c->get(ValidationGuard::class);
                assert($service instanceof StepUpService);
                assert($validationGuard instanceof ValidationGuard);
                return new StepUpController($service, $validationGuard);
            },

            // Phase Sx: Verification Code Infrastructure
            VerificationCodeRepositoryInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new PdoVerificationCodeRepository($pdo);
            },
            VerificationCodePolicyResolverInterface::class => function (ContainerInterface $c) {
                return new VerificationCodePolicyResolver();
            },
            VerificationCodeGeneratorInterface::class => function (ContainerInterface $c) {
                $repo = $c->get(VerificationCodeRepositoryInterface::class);
                $resolver = $c->get(VerificationCodePolicyResolverInterface::class);
                assert($repo instanceof VerificationCodeRepositoryInterface);
                assert($resolver instanceof VerificationCodePolicyResolverInterface);
                return new VerificationCodeGenerator($repo, $resolver);
            },
            VerificationCodeValidatorInterface::class => function (ContainerInterface $c) {
                $repo = $c->get(VerificationCodeRepositoryInterface::class);
                assert($repo instanceof VerificationCodeRepositoryInterface);
                return new VerificationCodeValidator($repo);
            },
            RecoveryStateService::class => function (ContainerInterface $c) {
                $auditWriter = $c->get(AuthoritativeSecurityAuditWriterInterface::class);
                $securityLogger = $c->get(SecurityEventLoggerInterface::class);
                $pdo = $c->get(PDO::class);
                $config = $c->get(AdminConfigDTO::class);

                assert($auditWriter instanceof AuthoritativeSecurityAuditWriterInterface);
                assert($securityLogger instanceof SecurityEventLoggerInterface);
                assert($pdo instanceof PDO);
                assert($config instanceof AdminConfigDTO);

                return new RecoveryStateService($auditWriter, $securityLogger, $pdo, $config);
            },
            RecoveryStateMiddleware::class => function (ContainerInterface $c) {
                $service = $c->get(RecoveryStateService::class);
                assert($service instanceof RecoveryStateService);
                return new RecoveryStateMiddleware($service);
            },
            RememberMeService::class => function (ContainerInterface $c) {
                $rememberMeRepo = $c->get(RememberMeRepositoryInterface::class);
                $sessionRepo = $c->get(AdminSessionRepositoryInterface::class);
                $securityLogger = $c->get(SecurityEventLoggerInterface::class);
                $clientInfo = $c->get(ClientInfoProviderInterface::class);
                $auditWriter = $c->get(AuthoritativeSecurityAuditWriterInterface::class);
                $pdo = $c->get(PDO::class);

                assert($rememberMeRepo instanceof RememberMeRepositoryInterface);
                assert($sessionRepo instanceof AdminSessionRepositoryInterface);
                assert($securityLogger instanceof SecurityEventLoggerInterface);
                assert($clientInfo instanceof ClientInfoProviderInterface);
                assert($auditWriter instanceof AuthoritativeSecurityAuditWriterInterface);
                assert($pdo instanceof PDO);

                return new RememberMeService(
                    $rememberMeRepo,
                    $sessionRepo,
                    $securityLogger,
                    $clientInfo,
                    $auditWriter,
                    $pdo
                );
            },
            RememberMeMiddleware::class => function (ContainerInterface $c) {
                $service = $c->get(RememberMeService::class);
                assert($service instanceof RememberMeService);
                return new RememberMeMiddleware($service);
            },

            // Crypto
            ReversibleCryptoAlgorithmRegistry::class => function (ContainerInterface $c) {
                $registry = new ReversibleCryptoAlgorithmRegistry();
                $registry->register(new Aes256GcmAlgorithm());
                return $registry;
            },
            KeyRotationService::class => function (ContainerInterface $c) {
                $config = $c->get(AdminConfigDTO::class);
                assert($config instanceof AdminConfigDTO);

                // Check if key is hex or raw.
                // Assuming it's hex if it looks like hex, otherwise raw.
                // Actually, standard is usually hex for ENV keys.
                // Let's assume hex and try to convert, or raw.
                // Safest: try hex2bin if ctype_xdigit, else use raw.
                $rawKey = $config->emailEncryptionKey;
                if (ctype_xdigit($rawKey)) {
                    $rawKey = hex2bin($rawKey);
                }

                $keys = [
                    new CryptoKeyDTO(
                        'v1',
                        (string) $rawKey,
                        KeyStatusEnum::ACTIVE,
                        new \DateTimeImmutable()
                    ),
                ];

                $provider = new InMemoryKeyProvider($keys);
                $policy = new StrictSingleActiveKeyPolicy();

                return new KeyRotationService($provider, $policy);
            },
            HKDFService::class => function (ContainerInterface $c) {
                return new HKDFService();
            },
            CryptoDirectFactory::class => function (ContainerInterface $c) {
                $rotation = $c->get(KeyRotationService::class);
                $registry = $c->get(ReversibleCryptoAlgorithmRegistry::class);
                assert($rotation instanceof KeyRotationService);
                assert($registry instanceof ReversibleCryptoAlgorithmRegistry);

                return new CryptoDirectFactory($rotation, $registry);
            },
            CryptoContextFactory::class => function (ContainerInterface $c) {
                $rotation = $c->get(KeyRotationService::class);
                $hkdf = $c->get(HKDFService::class);
                $registry = $c->get(ReversibleCryptoAlgorithmRegistry::class);
                assert($rotation instanceof KeyRotationService);
                assert($hkdf instanceof HKDFService);
                assert($registry instanceof ReversibleCryptoAlgorithmRegistry);

                return new CryptoContextFactory($rotation, $hkdf, $registry);
            },
            CryptoProvider::class => function (ContainerInterface $c) {
                $contextFactory = $c->get(CryptoContextFactory::class);
                $directFactory = $c->get(CryptoDirectFactory::class);
                $passwordService = $c->get(PasswordService::class);

                assert($contextFactory instanceof CryptoContextFactory);
                assert($directFactory instanceof CryptoDirectFactory);
                assert($passwordService instanceof PasswordService);

                return new CryptoProvider($contextFactory, $directFactory, $passwordService);
            },
        ]);

        return $containerBuilder->build();
    }
}
