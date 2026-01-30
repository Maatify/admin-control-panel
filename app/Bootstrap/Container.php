<?php

declare(strict_types=1);

namespace App\Bootstrap;

use App\Application\Admin\AdminProfileUpdateService;
use App\Application\Auth\AdminLoginService;
use App\Application\Auth\AdminLogoutService;
use App\Application\Crypto\AdminIdentifierCryptoServiceInterface;
use App\Application\Crypto\NotificationCryptoServiceInterface;
use App\Application\Crypto\PasswordCryptoServiceInterface;
use App\Application\Crypto\TotpSecretCryptoServiceInterface;
use App\Application\Verification\VerificationNotificationDispatcher;
use App\Application\Verification\VerificationNotificationDispatcherInterface;
use App\Context\ActorContext;
use App\Domain\Admin\Reader\AdminBasicInfoReaderInterface;
use App\Domain\Admin\Reader\AdminEmailReaderInterface;
use App\Domain\Admin\Reader\AdminProfileReaderInterface;
use App\Domain\Admin\Reader\AdminQueryReaderInterface;
use App\Domain\Contracts\ActorProviderInterface;
use App\Domain\Contracts\AdminDirectPermissionRepositoryInterface;
use App\Domain\Contracts\AdminEmailVerificationRepositoryInterface;
use App\Domain\Contracts\AdminIdentifierLookupInterface;
use App\Domain\Contracts\AdminNotificationChannelRepositoryInterface;
use App\Domain\Contracts\AdminNotificationHistoryReaderInterface;
use App\Domain\Contracts\AdminNotificationPersistenceWriterInterface;
use App\Domain\Contracts\AdminNotificationPreferenceReaderInterface;
use App\Domain\Contracts\AdminNotificationPreferenceRepositoryInterface;
use App\Domain\Contracts\AdminNotificationPreferenceWriterInterface;
use App\Domain\Contracts\AdminNotificationReadMarkerInterface;
use App\Domain\Contracts\AdminPasswordRepositoryInterface;
use App\Domain\Contracts\AdminRoleRepositoryInterface;
use App\Domain\Contracts\AdminSessionRepositoryInterface;
use App\Domain\Contracts\AdminTotpSecretRepositoryInterface;
use App\Domain\Contracts\AdminTotpSecretStoreInterface;
use App\Domain\Contracts\FailedNotificationRepositoryInterface;
use App\Domain\Contracts\NotificationReadRepositoryInterface;
use App\Domain\Contracts\NotificationRoutingInterface;
use App\Domain\Contracts\PermissionMapperInterface;
use App\Domain\Contracts\PermissionsMetadataRepositoryInterface;
use App\Domain\Contracts\PermissionsReaderRepositoryInterface;
use App\Domain\Contracts\RememberMeRepositoryInterface;
use App\Domain\Contracts\RolePermissionRepositoryInterface;
use App\Domain\Contracts\Roles\RoleCreateRepositoryInterface;
use App\Domain\Contracts\Roles\RoleRenameRepositoryInterface;
use App\Domain\Contracts\Roles\RoleRepositoryInterface;
use App\Domain\Contracts\Roles\RolesMetadataRepositoryInterface;
use App\Domain\Contracts\Roles\RolesReaderRepositoryInterface;
use App\Domain\Contracts\Roles\RoleToggleRepositoryInterface;
use App\Domain\Contracts\StepUpGrantRepositoryInterface;
use App\Domain\Contracts\TotpServiceInterface;
use App\Domain\Contracts\VerificationCodeGeneratorInterface;
use App\Domain\Contracts\VerificationCodePolicyResolverInterface;
use App\Domain\Contracts\VerificationCodeRepositoryInterface;
use App\Domain\Contracts\VerificationCodeValidatorInterface;
use App\Domain\DTO\AdminConfigDTO;
use App\Domain\DTO\TotpEnrollmentConfig;
use App\Domain\DTO\Ui\UiConfigDTO;
use App\Domain\Ownership\SystemOwnershipRepositoryInterface;
use App\Domain\Security\Crypto\CryptoKeyRingConfig;
use App\Domain\Security\Password\PasswordPepperRing;
use App\Domain\Security\Password\PasswordPepperRingConfig;
use App\Domain\Security\PermissionMapper;
use App\Domain\Service\AdminAuthenticationService;
use App\Domain\Service\AdminEmailVerificationService;
use App\Domain\Service\AdminNotificationRoutingService;
use App\Domain\Service\AuthorizationService;
use App\Domain\Service\PasswordService;
use App\Domain\Service\RecoveryStateService;
use App\Domain\Service\RememberMeService;
use App\Domain\Service\RoleAssignmentService;
use App\Domain\Service\RoleHierarchyComparator;
use App\Domain\Service\RoleLevelResolver;
use App\Domain\Service\SessionRevocationService;
use App\Domain\Service\StepUpService;
use App\Domain\Service\VerificationCodeGenerator;
use App\Domain\Service\VerificationCodePolicyResolver;
use App\Domain\Service\VerificationCodeValidator;
use App\Domain\Session\Reader\SessionListReaderInterface;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminEmailVerificationController;
use App\Http\Controllers\AdminNotificationHistoryController;
use App\Http\Controllers\AdminNotificationPreferenceController;
use App\Http\Controllers\AdminNotificationReadController;
use App\Http\Controllers\Api\PermissionMetadataUpdateController;
use App\Http\Controllers\Api\PermissionsController;
use App\Http\Controllers\Api\Roles\RoleCreateController;
use App\Http\Controllers\Api\Roles\RoleMetadataUpdateController;
use App\Http\Controllers\Api\Roles\RoleRenameController;
use App\Http\Controllers\Api\Roles\RolesControllerQuery;
use App\Http\Controllers\Api\Roles\RoleToggleController;
use App\Http\Controllers\Api\SessionBulkRevokeController;
use App\Http\Controllers\Api\SessionQueryController;
use App\Http\Controllers\Api\SessionRevokeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\NotificationQueryController;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Controllers\Ui\SessionListController;
use App\Http\Controllers\Ui\UiAdminsController;
use App\Http\Controllers\Ui\UiDashboardController;
use App\Http\Controllers\Ui\UiPermissionsController;
use App\Http\Controllers\Ui\UiRolesController;
use App\Http\Controllers\Ui\UiSettingsController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\EmailVerificationController;
use App\Http\Controllers\Web\LoginController;
use App\Http\Controllers\Web\LogoutController;
use App\Http\Controllers\Web\TelegramConnectController;
use App\Http\Controllers\Web\TwoFactorController;
use App\Http\Middleware\RecoveryStateMiddleware;
use App\Http\Middleware\RememberMeMiddleware;
use App\Http\Middleware\ScopeGuardMiddleware;
use App\Http\Middleware\SessionStateGuardMiddleware;
use App\Infrastructure\Admin\Reader\PDOAdminBasicInfoReader;
use App\Infrastructure\Admin\Reader\PDOAdminEmailReader;
use App\Infrastructure\Admin\Reader\PdoAdminProfileReader;
use App\Infrastructure\Context\ActorContextProvider;
use App\Infrastructure\Crypto\AdminIdentifierCryptoService;
use App\Infrastructure\Crypto\NotificationCryptoService;
use App\Infrastructure\Crypto\PasswordCryptoService;
use App\Infrastructure\Crypto\TotpSecretCryptoService;
use App\Infrastructure\Database\PDOFactory;
use App\Infrastructure\Notification\TelegramHandler;
use App\Infrastructure\Query\ListFilterResolver;
use App\Infrastructure\Reader\Admin\PdoAdminQueryReader;
use App\Infrastructure\Reader\PDOPermissionsReaderRepository;
use App\Infrastructure\Reader\PDORolesReaderRepository;
use App\Infrastructure\Reader\Session\PdoSessionListReader;
use App\Infrastructure\Repository\AdminEmailRepository;
use App\Infrastructure\Repository\AdminNotificationChannelRepository;
use App\Infrastructure\Repository\AdminPasswordRepository;
use App\Infrastructure\Repository\AdminRepository;
use App\Infrastructure\Repository\AdminRoleRepository;
use App\Infrastructure\Repository\AdminSessionRepository;
use App\Infrastructure\Repository\AdminTotpSecretRepository;
use App\Infrastructure\Repository\FailedNotificationRepository;
use App\Infrastructure\Repository\NotificationReadRepository;
use App\Infrastructure\Repository\PdoAdminDirectPermissionRepository;
use App\Infrastructure\Repository\PdoAdminNotificationHistoryReader;
use App\Infrastructure\Repository\PdoAdminNotificationPersistenceRepository;
use App\Infrastructure\Repository\PdoAdminNotificationPreferenceRepository;
use App\Infrastructure\Repository\PdoAdminNotificationReadMarker;
use App\Infrastructure\Repository\PdoRememberMeRepository;
use App\Infrastructure\Repository\PdoStepUpGrantRepository;
use App\Infrastructure\Repository\PdoSystemOwnershipRepository;
use App\Infrastructure\Repository\PdoVerificationCodeRepository;
use App\Infrastructure\Repository\RolePermissionRepository;
use App\Infrastructure\Repository\Roles\PdoRoleCreateRepository;
use App\Infrastructure\Repository\Roles\PdoRoleRepository;
use App\Infrastructure\Service\AdminTotpSecretStore;
use App\Infrastructure\Service\Google2faTotpService;
use App\Infrastructure\Updater\PDOPermissionsMetadataRepository;
use App\Kernel\Adapter\CryptoKeyRingEnvAdapter;
use App\Kernel\Adapter\PasswordPepperEnvAdapter;
use App\Kernel\DTO\AdminRuntimeConfigDTO;
use App\Modules\Crypto\DX\CryptoContextFactory;
use App\Modules\Crypto\DX\CryptoDirectFactory;
use App\Modules\Crypto\DX\CryptoProvider;
use App\Modules\Crypto\HKDF\HKDFService;
use App\Modules\Crypto\KeyRotation\DTO\CryptoKeyDTO;
use App\Modules\Crypto\KeyRotation\KeyRotationService;
use App\Modules\Crypto\KeyRotation\KeyStatusEnum;
use App\Modules\Crypto\KeyRotation\Policy\StrictSingleActiveKeyPolicy;
use App\Modules\Crypto\KeyRotation\Providers\InMemoryKeyProvider;
use App\Modules\Crypto\Reversible\Algorithms\Aes256GcmAlgorithm;
use App\Modules\Crypto\Reversible\Registry\ReversibleCryptoAlgorithmRegistry;
use App\Modules\Email\Config\EmailTransportConfigDTO;
use App\Modules\Email\Queue\EmailQueueWriterInterface;
use App\Modules\Email\Queue\PdoEmailQueueWriter;
use App\Modules\Email\Renderer\EmailRendererInterface;
use App\Modules\Email\Renderer\TwigEmailRenderer;
use App\Modules\Email\Transport\EmailTransportInterface;
use App\Modules\Email\Transport\SmtpEmailTransport;
use App\Modules\InputNormalization\Contracts\InputNormalizerInterface;
use App\Modules\InputNormalization\Middleware\InputNormalizationMiddleware;
use App\Modules\InputNormalization\Normalizer\InputNormalizer;
use App\Modules\Validation\Contracts\ValidatorInterface;
use App\Modules\Validation\Guard\ValidationGuard;
use App\Modules\Validation\Validator\RespectValidator;
use DI\ContainerBuilder;
use Exception;
use Maatify\PsrLogger\LoggerFactory;
use PDO;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;

class Container
{
    /**
     * @param   callable(ContainerBuilder): void|null  $builderHook
     * @phpstan-param callable(ContainerBuilder<\DI\Container>): void|null $builderHook
     *
     * @throws Exception
     */
    public static function create(
        AdminRuntimeConfigDTO $runtime,
        ?callable $builderHook = null
    ): ContainerInterface
    {
        $containerBuilder = new ContainerBuilder();

        $cryptoRing = CryptoKeyRingConfig::fromEnv(
            CryptoKeyRingEnvAdapter::adapt($runtime)
        );

        $passwordPepperConfig = PasswordPepperRingConfig::fromEnv(
            PasswordPepperEnvAdapter::adapt($runtime)
        );


        // Create Config DTO
        $config = new AdminConfigDTO(
            appEnv: $runtime->appEnv,
            appDebug: $runtime->appDebug,
            timezone: $runtime->appTimezone,
            passwordActivePepperId: $passwordPepperConfig->activeId(),
            dbHost: $runtime->dbHost,
            dbName: $runtime->dbName,
            dbUser: $runtime->dbUser,
            isRecoveryMode: $runtime->recoveryMode,
            activeKeyId: $cryptoRing->activeKeyId(),
            hasCryptoKeyRing: !empty($cryptoRing->keys()),
            hasPasswordPepperRing: !empty($passwordPepperConfig->peppers())
        );

        $uiConfigDTO = new UiConfigDTO(
            adminAssetBaseUrl: $runtime->assetBaseUrl,
            appName: $runtime->appName,
            logoUrl: $runtime->logoUrl,
            adminUrl: $runtime->adminUrl,
            hostTemplatePath: $runtime->hostTemplatePath
        );

        $totpEnrollmentConfig = new TotpEnrollmentConfig(
            $runtime->totpIssuer,
            $runtime->totpEnrollmentTtlSeconds
        );

        // Create Email Config DTO
        $emailConfig = new EmailTransportConfigDTO(
            host: $runtime->mailHost,
            port: $runtime->mailPort,
            username: $runtime->mailUsername,
            password: $runtime->mailPassword,
            fromAddress: $runtime->mailFromAddress,
            fromName: $runtime->mailFromName,
            encryption: $runtime->mailEncryption,
            timeoutSeconds: $runtime->mailTimeoutSeconds,
            charset: $runtime->mailCharset,
            debugLevel: $runtime->mailDebugLevel
        );

        // Enforce Timezone
        date_default_timezone_set($config->timezone);

        $containerBuilder->addDefinitions([
            AdminRuntimeConfigDTO::class => function () use ($runtime) {
                return $runtime;
            },
            AdminConfigDTO::class => function () use ($config) {
                return $config;
            },
            UiConfigDTO::class => function () use ($uiConfigDTO) {
                return $uiConfigDTO;
            },
            EmailTransportConfigDTO::class => function () use ($emailConfig) {
                return $emailConfig;
            },
            TotpEnrollmentConfig::class => function () use ($totpEnrollmentConfig) {
                return $totpEnrollmentConfig;
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
                $ownershipRepo = $c->get(SystemOwnershipRepositoryInterface::class);
                $permissionMapper = $c->get(PermissionMapperInterface::class);

                assert($adminRoleRepo instanceof AdminRoleRepositoryInterface);
                assert($rolePermissionRepo instanceof RolePermissionRepositoryInterface);
                assert($directPermissionRepo instanceof AdminDirectPermissionRepositoryInterface);
                assert($ownershipRepo instanceof SystemOwnershipRepositoryInterface);
                assert($permissionMapper instanceof PermissionMapperInterface);

                return new AuthorizationService(
                    $adminRoleRepo,
                    $rolePermissionRepo,
                    $directPermissionRepo,
                    $ownershipRepo,
                    $permissionMapper
                );
            },
            \App\Domain\Contracts\Ui\NavigationProviderInterface::class => function (ContainerInterface $c) {
                return new \App\Infrastructure\Ui\DefaultNavigationProvider();
            },
            Twig::class                                               => function (ContainerInterface $c) {
                $uiConfigDTO = $c->get(UiConfigDTO::class);
                assert($uiConfigDTO instanceof UiConfigDTO);

                $loader = new \Twig\Loader\FilesystemLoader();

                // 1. Host templates (Precedence)
                if ($uiConfigDTO->hostTemplatePath !== null && is_dir($uiConfigDTO->hostTemplatePath)) {
                    $hostPath = rtrim($uiConfigDTO->hostTemplatePath, '/');
                    $loader->addPath($hostPath);         // Main namespace (overrides)
                    $loader->addPath($hostPath, 'host'); // @host namespace
                }

                // 2. Kernel templates
                $kernelPath = __DIR__ . '/../../templates';
                $loader->addPath($kernelPath);          // Main namespace (fallback)
                $loader->addPath($kernelPath, 'admin'); // @admin namespace

                $twig = new Twig($loader, ['cache' => false]);

                $navProvider = $c->get(\App\Domain\Contracts\Ui\NavigationProviderInterface::class);
                assert($navProvider instanceof \App\Domain\Contracts\Ui\NavigationProviderInterface);

                $twig->getEnvironment()->addGlobal('nav_items', $navProvider->getNavigationItems());
                $twig->getEnvironment()->addGlobal('ui', $uiConfigDTO);

                $twig->getEnvironment()->addFunction(new \Twig\TwigFunction('asset', function (string $path) use ($uiConfigDTO): string {
                    return rtrim($uiConfigDTO->adminAssetBaseUrl, '/') . '/' . ltrim($path, '/');
                }));

                return $twig;
            },

            PDO::class => function (ContainerInterface $c) {
                $config = $c->get(AdminConfigDTO::class);
                $adminRuntimeConfigDTO = $c->get(AdminRuntimeConfigDTO::class);
                assert($config instanceof AdminConfigDTO);
                assert($adminRuntimeConfigDTO instanceof AdminRuntimeConfigDTO);

                $factory = new PDOFactory(
                    $config->dbHost,
                    $config->dbName,
                    $config->dbUser,
                    $adminRuntimeConfigDTO->dbPassword
                );
                return $factory->create();
            },
            PDOFactory::class => function (ContainerInterface $c) {
                $config = $c->get(AdminConfigDTO::class);
                $adminRuntimeConfigDTO = $c->get(AdminRuntimeConfigDTO::class);
                assert($config instanceof AdminConfigDTO);
                assert($adminRuntimeConfigDTO instanceof AdminRuntimeConfigDTO);
                return new PDOFactory(
                    $config->dbHost,
                    $config->dbName,
                    $config->dbUser,
                    $adminRuntimeConfigDTO->dbPassword
                );
            },
            AdminRepository::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new AdminRepository($pdo);
            },
            AdminController::class => function (ContainerInterface $c) {
                $adminRepo = $c->get(AdminRepository::class);
                $emailRepo = $c->get(AdminEmailRepository::class);
                $validationGuard = $c->get(ValidationGuard::class);
                $cryptoService = $c->get(AdminIdentifierCryptoServiceInterface::class);

                $passwordRepository = $c->get(\App\Domain\Contracts\AdminPasswordRepositoryInterface::class);
                $passwordService = $c->get(\App\Domain\Service\PasswordService::class);
                $pdo = $c->get(PDO::class);

                $emailReader = $c->get(AdminEmailReaderInterface::class);
                $basicInfoReader = $c->get(AdminBasicInfoReaderInterface::class);

                assert($adminRepo instanceof AdminRepository);
                assert($emailRepo instanceof AdminEmailRepository);
                assert($validationGuard instanceof ValidationGuard);
                assert($cryptoService instanceof AdminIdentifierCryptoServiceInterface);

                assert($passwordRepository instanceof \App\Domain\Contracts\AdminPasswordRepositoryInterface);
                assert($passwordService instanceof \App\Domain\Service\PasswordService);
                assert($pdo instanceof PDO);

                assert($emailReader instanceof AdminEmailReaderInterface);
                assert($basicInfoReader instanceof AdminBasicInfoReaderInterface);


                return new AdminController(
                    $adminRepo,
                    $emailRepo,
                    $validationGuard,
                    $cryptoService,
                    $passwordRepository,
                    $passwordService,
                    $pdo,
                    $emailReader,
                    $basicInfoReader,
                );
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
                $pdo = $c->get(PDO::class);

                assert($repo instanceof AdminEmailVerificationRepositoryInterface);
                assert($pdo instanceof PDO);

                return new AdminEmailVerificationService($repo, $pdo);
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
            PasswordPepperRing::class => function (ContainerInterface $c) use ($passwordPepperConfig) {
                return $passwordPepperConfig->ring();
            },
            PasswordService::class => function (ContainerInterface $c) {
                $ring = $c->get(PasswordPepperRing::class);
                $adminRuntimeConfigDTO = $c->get(AdminRuntimeConfigDTO::class);

                assert($ring instanceof PasswordPepperRing);
                assert($adminRuntimeConfigDTO instanceof AdminRuntimeConfigDTO);
                
                /** @var mixed $options */
                $options = json_decode($adminRuntimeConfigDTO->passwordArgon2Options, true, 512, JSON_THROW_ON_ERROR);
                
                if (!is_array($options)) {
                    throw new \Exception('PASSWORD_ARGON2_OPTIONS must be a valid JSON object.');
                }

                // Validate exact keys
                $requiredKeys = ['memory_cost', 'threads', 'time_cost'];
                $keys = array_keys($options);
                sort($keys);
                sort($requiredKeys);
                
                if ($keys !== $requiredKeys) {
                     throw new \Exception('PASSWORD_ARGON2_OPTIONS must contain exactly: memory_cost, time_cost, threads.');
                }

                // Validate values
                foreach ($options as $key => $value) {
                    if (!is_int($value) || $value <= 0) {
                        throw new \Exception("PASSWORD_ARGON2_OPTIONS key '$key' must be a positive integer.");
                    }
                }

                /** @var array{memory_cost: int, time_cost: int, threads: int} $options */
                return new PasswordService($ring, $options);
            },
            AdminAuthenticationService::class => function (ContainerInterface $c) {
                $lookup = $c->get(AdminIdentifierLookupInterface::class);
                $passwordRepo = $c->get(AdminPasswordRepositoryInterface::class);
                $sessionRepo = $c->get(AdminSessionRepositoryInterface::class);
                $recoveryState = $c->get(RecoveryStateService::class);
                $pdo = $c->get(PDO::class);
                $passwordService = $c->get(PasswordService::class);
                $adminRepository = $c->get(AdminRepository::class);

                assert($lookup instanceof AdminIdentifierLookupInterface);
                assert($passwordRepo instanceof AdminPasswordRepositoryInterface);
                assert($sessionRepo instanceof AdminSessionRepositoryInterface);
                assert($recoveryState instanceof RecoveryStateService);
                assert($pdo instanceof PDO);
                assert($passwordService instanceof PasswordService);
                assert($adminRepository instanceof AdminRepository);

                return new AdminAuthenticationService(
                    $lookup,
                    $passwordRepo,
                    $sessionRepo,
                    $recoveryState,
                    $pdo,
                    $passwordService,
                    $adminRepository
                );
            },
            AdminSessionRepositoryInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new AdminSessionRepository($pdo);
            },
            \App\Domain\Contracts\AdminSessionValidationRepositoryInterface::class => function (ContainerInterface $c) {
                return $c->get(AdminSessionRepositoryInterface::class);
            },
            SessionRevocationService::class => function (ContainerInterface $c) {
                $repo = $c->get(\App\Domain\Contracts\AdminSessionValidationRepositoryInterface::class);
                $pdo = $c->get(PDO::class);

                assert($repo instanceof \App\Domain\Contracts\AdminSessionValidationRepositoryInterface);
                assert($pdo instanceof PDO);

                return new SessionRevocationService($repo, $pdo);
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
                $pdo = $c->get(PDO::class);

                assert($recoveryState instanceof RecoveryStateService);
                assert($stepUpService instanceof StepUpService);
                assert($grantRepo instanceof StepUpGrantRepositoryInterface);
                assert($hierarchyComparator instanceof RoleHierarchyComparator);
                assert($adminRoleRepo instanceof AdminRoleRepositoryInterface);
                assert($pdo instanceof PDO);

                return new RoleAssignmentService(
                    $recoveryState,
                    $stepUpService,
                    $grantRepo,
                    $hierarchyComparator,
                    $adminRoleRepo,
                    $pdo
                );
            },
            LoggerInterface::class => function () {
                return LoggerFactory::create('slim/app');
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
            AuthController::class => function (ContainerInterface $c) {
                $authService = $c->get(AdminAuthenticationService::class);
                $validationGuard = $c->get(ValidationGuard::class);

                // Crypto
                $cryptoService = $c->get(AdminIdentifierCryptoServiceInterface::class);


                assert($authService instanceof AdminAuthenticationService);
                assert($validationGuard instanceof ValidationGuard);
                assert($cryptoService instanceof AdminIdentifierCryptoServiceInterface);


                return new AuthController(
                    $authService,
                    $cryptoService,
                    $validationGuard
                );
            },
            AdminLoginService::class => function (ContainerInterface $c) {
                $authService = $c->get(AdminAuthenticationService::class);
                $sessionRepo = $c->get(\App\Domain\Contracts\AdminSessionValidationRepositoryInterface::class);
                $rememberMeService = $c->get(RememberMeService::class);
                $cryptoService = $c->get(AdminIdentifierCryptoServiceInterface::class);

                assert($authService instanceof AdminAuthenticationService);
                assert($sessionRepo instanceof \App\Domain\Contracts\AdminSessionValidationRepositoryInterface);
                assert($rememberMeService instanceof RememberMeService);
                assert($cryptoService instanceof AdminIdentifierCryptoServiceInterface);

                return new AdminLoginService($authService, $sessionRepo, $rememberMeService, $cryptoService);
            },
            LoginController::class => function (ContainerInterface $c) {
                $adminLoginService = $c->get(AdminLoginService::class);
                $view = $c->get(Twig::class);
                assert($adminLoginService instanceof AdminLoginService);
                assert($view instanceof Twig);

                return new LoginController(
                    $adminLoginService,
                    $view,
                );
            },
            \App\Application\Auth\AdminLogoutService::class => function (ContainerInterface $c) {
                $sessionRepo = $c->get(\App\Domain\Contracts\AdminSessionValidationRepositoryInterface::class);
                $rememberMeService = $c->get(RememberMeService::class);
                $authService = $c->get(AdminAuthenticationService::class);

                // Telemetry
                $telemetryService = $c->get(\App\Application\Services\DiagnosticsTelemetryService::class);

                assert($sessionRepo instanceof \App\Domain\Contracts\AdminSessionValidationRepositoryInterface);
                assert($rememberMeService instanceof RememberMeService);
                assert($authService instanceof AdminAuthenticationService);
                assert($telemetryService instanceof \App\Application\Services\DiagnosticsTelemetryService);

                return new AdminLogoutService($sessionRepo, $rememberMeService, $authService, $telemetryService);
            },
            LogoutController::class => function (ContainerInterface $c) {
                $adminLogoutService = $c->get(\App\Application\Auth\AdminLogoutService::class);
                assert($adminLogoutService instanceof \App\Application\Auth\AdminLogoutService);

                return new LogoutController($adminLogoutService);
            },
            \App\Application\Auth\ChangePasswordService::class => function (ContainerInterface $c) {
                $cryptoService = $c->get(AdminIdentifierCryptoServiceInterface::class);
                $identifierLookup = $c->get(AdminIdentifierLookupInterface::class);
                $passwordRepo = $c->get(AdminPasswordRepositoryInterface::class);
                $passwordService = $c->get(PasswordService::class);
                $recoveryState = $c->get(RecoveryStateService::class);
                $pdo = $c->get(PDO::class);

                assert($cryptoService instanceof AdminIdentifierCryptoServiceInterface);
                assert($identifierLookup instanceof AdminIdentifierLookupInterface);
                assert($passwordRepo instanceof AdminPasswordRepositoryInterface);
                assert($passwordService instanceof PasswordService);
                assert($recoveryState instanceof RecoveryStateService);
                assert($pdo instanceof PDO);

                return new \App\Application\Auth\ChangePasswordService(
                    $cryptoService,
                    $identifierLookup,
                    $passwordRepo,
                    $passwordService,
                    $recoveryState,
                    $pdo
                );
            },
            \App\Http\Controllers\Web\ChangePasswordController::class => function (ContainerInterface $c) {
                $view = $c->get(Twig::class);
                $changePasswordService = $c->get(\App\Application\Auth\ChangePasswordService::class);

                assert($view instanceof Twig);
                assert($changePasswordService instanceof \App\Application\Auth\ChangePasswordService);

                return new \App\Http\Controllers\Web\ChangePasswordController($view, $changePasswordService);
            },
            \App\Application\Auth\VerifyEmailService::class => function (ContainerInterface $c) {
                $cryptoService = $c->get(AdminIdentifierCryptoServiceInterface::class);
                $lookupInterface = $c->get(AdminIdentifierLookupInterface::class);
                $validator = $c->get(VerificationCodeValidatorInterface::class);
                $verificationService = $c->get(AdminEmailVerificationService::class);

                assert($cryptoService instanceof AdminIdentifierCryptoServiceInterface);
                assert($lookupInterface instanceof AdminIdentifierLookupInterface);
                assert($validator instanceof VerificationCodeValidatorInterface);
                assert($verificationService instanceof AdminEmailVerificationService);

                return new \App\Application\Auth\VerifyEmailService(
                    $cryptoService,
                    $lookupInterface,
                    $validator,
                    $verificationService
                );
            },

            \App\Application\Auth\ResendEmailVerificationService::class => function (ContainerInterface $c) {
                $cryptoService = $c->get(AdminIdentifierCryptoServiceInterface::class);
                $lookupInterface = $c->get(AdminIdentifierLookupInterface::class);
                $generator = $c->get(VerificationCodeGeneratorInterface::class);
                $dispatcher = $c->get(VerificationNotificationDispatcherInterface::class);

                assert($cryptoService instanceof AdminIdentifierCryptoServiceInterface);
                assert($lookupInterface instanceof AdminIdentifierLookupInterface);
                assert($generator instanceof VerificationCodeGeneratorInterface);
                assert($dispatcher instanceof VerificationNotificationDispatcherInterface);

                return new \App\Application\Auth\ResendEmailVerificationService(
                    $cryptoService,
                    $lookupInterface,
                    $generator,
                    $dispatcher
                );
            },
            EmailVerificationController::class => function (ContainerInterface $c) {

                $view = $c->get(Twig::class);
                $verifyEmailService = $c->get(\App\Application\Auth\VerifyEmailService::class);
                $resendEmailVerificationService = $c->get(\App\Application\Auth\ResendEmailVerificationService::class);

                assert($view instanceof Twig);
                assert($verifyEmailService instanceof \App\Application\Auth\VerifyEmailService);
                assert($resendEmailVerificationService instanceof \App\Application\Auth\ResendEmailVerificationService);

                return new EmailVerificationController(
                    $view,
                    $verifyEmailService,
                    $resendEmailVerificationService
                );
            },
            VerificationNotificationDispatcherInterface::class => function (ContainerInterface $c) {
                $emailQueue = $c->get(EmailQueueWriterInterface::class);
                $logger = $c->get(LoggerInterface::class);
                assert($emailQueue instanceof EmailQueueWriterInterface);
                assert($logger instanceof LoggerInterface);
                return new VerificationNotificationDispatcher($emailQueue, $logger);
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

            AdminProfileReaderInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                $cryptoService = $c->get(AdminIdentifierCryptoServiceInterface::class);
                assert($pdo instanceof PDO);
                assert($cryptoService instanceof AdminIdentifierCryptoServiceInterface);
                return new PdoAdminProfileReader(
                    $pdo,
                    $cryptoService
                );
            },

            AdminBasicInfoReaderInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new PdoAdminBasicInfoReader(
                    $pdo,
                );
            },

            AdminEmailReaderInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                $cryptoService = $c->get(AdminIdentifierCryptoServiceInterface::class);
                assert($pdo instanceof PDO);
                assert($cryptoService instanceof AdminIdentifierCryptoServiceInterface);

                return new PdoAdminEmailReader(
                    $pdo,
                    $cryptoService
                );

            },

            UiAdminsController::class => function (ContainerInterface $c) {
                $view = $c->get(Twig::class);
                $profileReader = $c->get(AdminProfileReaderInterface::class);
                $profileUpdateService = $c->get(AdminProfileUpdateService::class);
                $emailReaderInterface = $c->get(AdminEmailReaderInterface::class);
                $basicInfoReaderInterface = $c->get(AdminBasicInfoReaderInterface::class);
                assert($view instanceof Twig);
                assert($profileReader instanceof AdminProfileReaderInterface);
                assert($profileUpdateService instanceof AdminProfileUpdateService);
                assert($emailReaderInterface instanceof AdminEmailReaderInterface);
                assert($basicInfoReaderInterface instanceof AdminBasicInfoReaderInterface);
                return new UiAdminsController(
                    $view,
                    $profileReader,
                    $profileUpdateService,
                    $emailReaderInterface,
                    $basicInfoReaderInterface
                );
            },
            UiDashboardController::class => function (ContainerInterface $c) {
                $webDashboard = $c->get(DashboardController::class);
                assert($webDashboard instanceof DashboardController);
                return new UiDashboardController($webDashboard);
            },
            UiPermissionsController::class => function (ContainerInterface $c) {
                $view = $c->get(Twig::class);
                $authorizationService = $c->get(AuthorizationService::class);
                assert($view instanceof Twig);
                assert($authorizationService instanceof AuthorizationService);
                return new UiPermissionsController($view, $authorizationService);
            },
            UiRolesController::class => function (ContainerInterface $c) {
                $view = $c->get(Twig::class);
                $authorizationService = $c->get(AuthorizationService::class);
                assert($view instanceof Twig);
                assert($authorizationService instanceof AuthorizationService);
                return new UiRolesController($view, $authorizationService);
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
            \App\Application\Auth\TwoFactorEnrollmentService::class => function (ContainerInterface $c) {
                $stepUpService = $c->get(StepUpService::class);
                $totpService = $c->get(TotpServiceInterface::class);
                $telemetryService = $c->get(\App\Application\Services\DiagnosticsTelemetryService::class);

                assert($stepUpService instanceof StepUpService);
                assert($totpService instanceof TotpServiceInterface);
                assert($telemetryService instanceof \App\Application\Services\DiagnosticsTelemetryService);

                return new \App\Application\Auth\TwoFactorEnrollmentService($stepUpService, $totpService, $telemetryService);
            },
            \App\Application\Auth\TwoFactorVerificationService::class => function (ContainerInterface $c) {
                $stepUpService = $c->get(StepUpService::class);
                $telemetryService = $c->get(\App\Application\Services\DiagnosticsTelemetryService::class);

                assert($stepUpService instanceof StepUpService);
                assert($telemetryService instanceof \App\Application\Services\DiagnosticsTelemetryService);

                return new \App\Application\Auth\TwoFactorVerificationService($stepUpService, $telemetryService);

            },
            TwoFactorController::class => function (ContainerInterface $c) {
                $enrollmentService = $c->get(\App\Application\Auth\TwoFactorEnrollmentService::class);
                $verificationService = $c->get(\App\Application\Auth\TwoFactorVerificationService::class);
                $view = $c->get(Twig::class);

                assert($enrollmentService instanceof \App\Application\Auth\TwoFactorEnrollmentService);
                assert($verificationService instanceof \App\Application\Auth\TwoFactorVerificationService);
                assert($view instanceof Twig);

                return new TwoFactorController($enrollmentService, $verificationService, $view);
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

            // Phase 14.3: Sessions
            SessionListReaderInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                $cryptoService = $c->get(AdminIdentifierCryptoServiceInterface::class);

                assert($pdo instanceof PDO);
                assert($cryptoService instanceof AdminIdentifierCryptoServiceInterface);

                return new PdoSessionListReader(
                    $pdo,
                    $cryptoService
                );
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
                $telemetryService = $c->get(\App\Application\Services\DiagnosticsTelemetryService::class);

                assert($reader instanceof SessionListReaderInterface);
                assert($auth instanceof AuthorizationService);
                assert($validationGuard instanceof ValidationGuard);
                assert($filterResolver instanceof \App\Infrastructure\Query\ListFilterResolver);
                assert($telemetryService instanceof \App\Application\Services\DiagnosticsTelemetryService);


                return new SessionQueryController($reader, $auth, $validationGuard, $filterResolver, $telemetryService);
            },
            SessionRevokeController::class => function (ContainerInterface $c) {
                $service = $c->get(SessionRevocationService::class);
                $auth = $c->get(AuthorizationService::class);
                $validationGuard = $c->get(ValidationGuard::class);

                assert($service instanceof SessionRevocationService);
                assert($auth instanceof AuthorizationService);
                assert($validationGuard instanceof ValidationGuard);

                return new SessionRevokeController(
                    $service,
                    $auth,
                    $validationGuard
                );
            },
            SessionBulkRevokeController::class => function (ContainerInterface $c) {
                $service = $c->get(SessionRevocationService::class);
                $auth = $c->get(AuthorizationService::class);
                $validationGuard = $c->get(ValidationGuard::class);

                assert($service instanceof SessionRevocationService);
                assert($auth instanceof AuthorizationService);
                assert($validationGuard instanceof ValidationGuard);

                return new SessionBulkRevokeController(
                    $service,
                    $auth,
                    $validationGuard,
                );
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
                $cryptoService = $c->get(AdminIdentifierCryptoServiceInterface::class);

                assert($pdo instanceof PDO);
                assert($cryptoService instanceof AdminIdentifierCryptoServiceInterface);

                return new PdoAdminQueryReader(
                    $pdo,
                    $cryptoService
                );
            },

            // Phase 12
            StepUpGrantRepositoryInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new PdoStepUpGrantRepository($pdo);
            },
            AdminTotpSecretRepositoryInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new AdminTotpSecretRepository(
                    $pdo
                );
            },
            TotpServiceInterface::class => function (ContainerInterface $c) {
                return new Google2faTotpService();
            },
            AdminTotpSecretStoreInterface::class => function (ContainerInterface $c) {
                $adminTotpSecretRepository = $c->get(AdminTotpSecretRepositoryInterface::class);
                $totpSecretCryptoService = $c->get(TotpSecretCryptoServiceInterface::class);
                assert($adminTotpSecretRepository instanceof AdminTotpSecretRepositoryInterface);
                assert($totpSecretCryptoService instanceof TotpSecretCryptoServiceInterface);
                return new AdminTotpSecretStore(
                    $adminTotpSecretRepository,
                    $totpSecretCryptoService
                );
            },
            StepUpService::class => function (ContainerInterface $c) {
                $grantRepo = $c->get(StepUpGrantRepositoryInterface::class);
                $totpSecretStore = $c->get(AdminTotpSecretStoreInterface::class);
                $totpService = $c->get(TotpServiceInterface::class);
                $recoveryState = $c->get(RecoveryStateService::class);
                $pdo = $c->get(PDO::class);

                assert($grantRepo instanceof StepUpGrantRepositoryInterface);
                assert($totpSecretStore instanceof AdminTotpSecretStoreInterface);
                assert($totpService instanceof TotpServiceInterface);
                assert($recoveryState instanceof RecoveryStateService);
                assert($pdo instanceof PDO);

                return new StepUpService(
                    $grantRepo,
                    $totpSecretStore,
                    $totpService,
                    $recoveryState,
                    $pdo
                );
            },
            SessionStateGuardMiddleware::class => function (ContainerInterface $c) {
                $service = $c->get(StepUpService::class);
                $repo = $c->get(AdminTotpSecretStoreInterface::class);
                assert($service instanceof StepUpService);
                assert($repo instanceof AdminTotpSecretStoreInterface);
                return new SessionStateGuardMiddleware($service, $repo);
            },
            ScopeGuardMiddleware::class => function (ContainerInterface $c) {
                $service = $c->get(StepUpService::class);
                assert($service instanceof StepUpService);
                return new ScopeGuardMiddleware($service);
            },
            \App\Http\Controllers\StepUpController::class => function (ContainerInterface $c) {
                $stepUpService = $c->get(\App\Domain\Service\StepUpService::class);
                $validationGuard = $c->get(\App\Modules\Validation\Guard\ValidationGuard::class);
                $telemetryService = $c->get(\App\Application\Services\DiagnosticsTelemetryService::class);

                assert($stepUpService instanceof \App\Domain\Service\StepUpService);
                assert($validationGuard instanceof \App\Modules\Validation\Guard\ValidationGuard);
                assert($telemetryService instanceof \App\Application\Services\DiagnosticsTelemetryService);

                return new \App\Http\Controllers\StepUpController(
                    $stepUpService,
                    $validationGuard,
                    $telemetryService
                );
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
                $pdo = $c->get(PDO::class);
                $config = $c->get(AdminConfigDTO::class);
                $adminRuntimeConfigDTO = $c->get(AdminRuntimeConfigDTO::class);

                assert($pdo instanceof PDO);
                assert($config instanceof AdminConfigDTO);
                assert($adminRuntimeConfigDTO instanceof AdminRuntimeConfigDTO);

                return new RecoveryStateService(
                    $pdo,
                    $config,
                    $adminRuntimeConfigDTO->emailBlindIndexKey
                );
            },
            RecoveryStateMiddleware::class => function (ContainerInterface $c) {
                $service = $c->get(RecoveryStateService::class);
                assert($service instanceof RecoveryStateService);
                return new RecoveryStateMiddleware($service);
            },
            RememberMeService::class => function (ContainerInterface $c) {
                $rememberMeRepo = $c->get(RememberMeRepositoryInterface::class);
                $sessionRepo = $c->get(AdminSessionRepositoryInterface::class);
                $pdo = $c->get(PDO::class);

                assert($rememberMeRepo instanceof RememberMeRepositoryInterface);
                assert($sessionRepo instanceof AdminSessionRepositoryInterface);
                assert($pdo instanceof PDO);

                return new RememberMeService(
                    $rememberMeRepo,
                    $sessionRepo,
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
            KeyRotationService::class => function (ContainerInterface $c) use ($cryptoRing) {
                $config = $c->get(AdminConfigDTO::class);
                assert($config instanceof AdminConfigDTO);

                $activeKeyId = $config->activeKeyId;
                if ($activeKeyId === null || $activeKeyId === '') {
                    throw new \Exception('CRYPTO_ACTIVE_KEY_ID is strictly required.');
                }

                $keys = [];

                // Fail-closed: CRYPTO_KEYS is strictly required (enforced in Config DTO, but double-checked here implicitly)
                foreach ($cryptoRing->keys() as $keyData) {
                    if ($keyData['id'] === '' || $keyData['key'] === '') {
                        throw new \Exception('Invalid crypto key structure. "id" and "key" must be non-empty.');
                    }

                    $rawKey = (string) $keyData['key'];
                    if (ctype_xdigit($rawKey)) {
                        $rawKey = hex2bin($rawKey);
                    }

                    if ($rawKey === false) {
                        throw new \Exception('Failed to decode hex key for ID: ' . $keyData['id']);
                    }

                    $status = ($keyData['id'] === $activeKeyId)
                        ? KeyStatusEnum::ACTIVE
                        : KeyStatusEnum::INACTIVE;

                    $keys[] = new CryptoKeyDTO(
                        (string) $keyData['id'],
                        (string) $rawKey,
                        $status,
                        new \DateTimeImmutable()
                    );
                }

                // Validate that the active key ID actually exists in the provided keys
                $activeFound = false;
                foreach ($keys as $key) {
                    if ($key->id() === $activeKeyId) {
                        $activeFound = true;
                        break;
                    }
                }

                if (!$activeFound) {
                    throw new \Exception("CRYPTO_ACTIVE_KEY_ID '{$activeKeyId}' not found in CRYPTO_KEYS.");
                }

                // Strict Status Enforcement
                $activeCount = 0;
                foreach ($keys as $key) {
                    if ($key->status() === KeyStatusEnum::ACTIVE) {
                        $activeCount++;
                    }
                }

                if ($activeCount !== 1) {
                    throw new \Exception("Crypto Configuration Error: Exactly ONE active key is required. Found: {$activeCount}");
                }

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
            EmailQueueWriterInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                $crypto = $c->get(CryptoProvider::class);

                assert($pdo instanceof PDO);
                assert($crypto instanceof CryptoProvider);

                return new PdoEmailQueueWriter($pdo, $crypto);
            },
            EmailRendererInterface::class => function (ContainerInterface $c) {
                return new TwigEmailRenderer();
            },
            EmailTransportInterface::class => function (ContainerInterface $c) {
                $config = $c->get(EmailTransportConfigDTO::class);
                assert($config instanceof EmailTransportConfigDTO);
                return new SmtpEmailTransport($config);
            },

            NotificationCryptoServiceInterface::class => function (ContainerInterface $c) {
                $cryptoProvider = $c->get(CryptoProvider::class);

                assert($cryptoProvider instanceof CryptoProvider);

                return new NotificationCryptoService($cryptoProvider);
            },

            TotpSecretCryptoServiceInterface::class => function (ContainerInterface $c) {
                $cryptoProvider = $c->get(CryptoProvider::class);

                assert($cryptoProvider instanceof CryptoProvider);

                return new TotpSecretCryptoService($cryptoProvider);
            },


            AdminIdentifierCryptoServiceInterface::class => function (ContainerInterface $c) {
                $cryptoProvider = $c->get(CryptoProvider::class);

                $adminRuntimeConfigDTO = $c->get(AdminRuntimeConfigDTO::class);

                assert($cryptoProvider instanceof CryptoProvider);
                assert($adminRuntimeConfigDTO instanceof AdminRuntimeConfigDTO);

                $blindIndexPepper = $adminRuntimeConfigDTO->emailBlindIndexKey;

                return new AdminIdentifierCryptoService(
                    $cryptoProvider,
                    $blindIndexPepper
                );
            },


            PasswordCryptoServiceInterface::class => function (ContainerInterface $c) {
                $passwordService = $c->get(PasswordService::class);

                assert($passwordService instanceof PasswordService);

                return new PasswordCryptoService($passwordService);
            },

            // ─────────────────────────────
            // Telemetry – UI Support
            // ─────────────────────────────

            ActorContext::class => function () {
                return new ActorContext();
            },

            ActorProviderInterface::class => function ($c) {
                $actorContext = $c->get(ActorContext::class);
                assert($actorContext instanceof ActorContext);
                return new ActorContextProvider(
                    $actorContext
                );
            },

            // ─────────────────────────────
            // Permissions
            // ─────────────────────────────

            PermissionsReaderRepositoryInterface::class => function ($c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new PDOPermissionsReaderRepository($pdo);
            },

            PermissionsController::class => function ($c) {
                $reader = $c->get(PermissionsReaderRepositoryInterface::class);
                $validationGuard = $c->get(ValidationGuard::class);
                $filterResolver = $c->get(ListFilterResolver::class);

                assert($reader instanceof PermissionsReaderRepositoryInterface);
                assert($validationGuard instanceof ValidationGuard);
                assert($filterResolver instanceof ListFilterResolver);

                return new PermissionsController($reader, $validationGuard, $filterResolver);
            },

            PermissionsMetadataRepositoryInterface::class => function ($c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new PdoPermissionsMetadataRepository($pdo);
            },

            PermissionMetadataUpdateController::class => function ($c) {

                $validationGuard = $c->get(ValidationGuard::class);
                $updater = $c->get(PermissionsMetadataRepositoryInterface::class);

                assert($validationGuard instanceof ValidationGuard);
                assert($updater instanceof PermissionsMetadataRepositoryInterface);

                return new PermissionMetadataUpdateController($validationGuard, $updater);
            },

            // ─────────────────────────────
            // Roles
            // ─────────────────────────────

            RolesReaderRepositoryInterface::class => function ($c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new PDORolesReaderRepository($pdo);
            },

            RolesMetadataRepositoryInterface::class => function ($c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new PdoRoleRepository($pdo);
            },

            RoleToggleRepositoryInterface::class => function ($c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new PdoRoleRepository($pdo);
            },

            RoleRenameRepositoryInterface::class => function ($c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new PdoRoleRepository($pdo);
            },

            RoleCreateRepositoryInterface::class => function ($c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new PdoRoleCreateRepository($pdo);
            },

            RolesControllerQuery::class => function ($c) {
                $reader = $c->get(RolesReaderRepositoryInterface::class);
                $validationGuard = $c->get(ValidationGuard::class);
                $filterResolver = $c->get(ListFilterResolver::class);
                assert($reader instanceof RolesReaderRepositoryInterface);
                assert($validationGuard instanceof ValidationGuard);
                assert($filterResolver instanceof ListFilterResolver);
                return new RolesControllerQuery($reader, $validationGuard, $filterResolver);
            },

            RoleMetadataUpdateController::class => function ($c) {
                $validationGuard = $c->get(ValidationGuard::class);
                $updater = $c->get(RolesMetadataRepositoryInterface::class);
                assert($validationGuard instanceof ValidationGuard);
                assert($updater instanceof RolesMetadataRepositoryInterface);
                return new RoleMetadataUpdateController($validationGuard, $updater);
            },

            RoleToggleController::class => function ($c) {
                $validationGuard = $c->get(ValidationGuard::class);
                $repo = $c->get(RoleToggleRepositoryInterface::class);

                assert($validationGuard instanceof ValidationGuard);
                assert($repo instanceof RoleToggleRepositoryInterface);

                return new RoleToggleController($validationGuard, $repo);
            },

            RoleRenameController::class => function ($c) {
                $validationGuard = $c->get(ValidationGuard::class);
                $repo = $c->get(RoleRenameRepositoryInterface::class);
                assert($validationGuard instanceof ValidationGuard);
                assert($repo instanceof RoleRenameRepositoryInterface);
                return new RoleRenameController($validationGuard, $repo);
            },

            RoleCreateController::class => function ($c) {
                $validationGuard = $c->get(ValidationGuard::class);
                $repo = $c->get(RoleCreateRepositoryInterface::class);
                assert($validationGuard instanceof ValidationGuard);
                assert($repo instanceof RoleCreateRepositoryInterface);
                return new RoleCreateController($validationGuard, $repo);
            },

            // ─────────────────────────────
            // New Logging Infrastructure
            // ─────────────────────────────

            // 1. Audit Trail
            \Maatify\AuditTrail\Contract\AuditTrailLoggerInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new \Maatify\AuditTrail\Infrastructure\Mysql\AuditTrailLoggerMysqlRepository($pdo);
            },
            \Maatify\AuditTrail\Services\ClockInterface::class => function () {
                return new \Maatify\AuditTrail\Services\SystemClock();
            },
            \Maatify\AuditTrail\Recorder\AuditTrailRecorder::class => function (ContainerInterface $c) {
                $logger = $c->get(\Maatify\AuditTrail\Contract\AuditTrailLoggerInterface::class);
                $clock = $c->get(\Maatify\AuditTrail\Services\ClockInterface::class);
                $fallbackLogger = $c->get(LoggerInterface::class);

                assert($logger instanceof \Maatify\AuditTrail\Contract\AuditTrailLoggerInterface);
                assert($clock instanceof \Maatify\AuditTrail\Services\ClockInterface);

                return new \Maatify\AuditTrail\Recorder\AuditTrailRecorder($logger, $clock, $fallbackLogger instanceof LoggerInterface ? $fallbackLogger : null);
            },
            \App\Application\Contracts\AuditTrailRecorderInterface::class => function (ContainerInterface $c) {
                $recorder = $c->get(\Maatify\AuditTrail\Recorder\AuditTrailRecorder::class);
                assert($recorder instanceof \Maatify\AuditTrail\Recorder\AuditTrailRecorder);
                return new \App\Infrastructure\Logging\AuditTrailMaatifyAdapter($recorder);
            },

            // 2. Authoritative Audit
            \Maatify\AuthoritativeAudit\Contract\AuthoritativeAuditOutboxWriterInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new \Maatify\AuthoritativeAudit\Infrastructure\Mysql\AuthoritativeAuditOutboxWriterMysqlRepository($pdo);
            },
            \Maatify\AuthoritativeAudit\Services\ClockInterface::class => function () {
                return new \Maatify\AuthoritativeAudit\Services\SystemClock();
            },
            \Maatify\AuthoritativeAudit\Recorder\AuthoritativeAuditRecorder::class => function (ContainerInterface $c) {
                $writer = $c->get(\Maatify\AuthoritativeAudit\Contract\AuthoritativeAuditOutboxWriterInterface::class);
                $clock = $c->get(\Maatify\AuthoritativeAudit\Services\ClockInterface::class);
                // AuthoritativeAuditRecorder does NOT accept a fallback logger in constructor

                assert($writer instanceof \Maatify\AuthoritativeAudit\Contract\AuthoritativeAuditOutboxWriterInterface);
                assert($clock instanceof \Maatify\AuthoritativeAudit\Services\ClockInterface);

                return new \Maatify\AuthoritativeAudit\Recorder\AuthoritativeAuditRecorder($writer, $clock);
            },
            \App\Application\Contracts\AuthoritativeAuditRecorderInterface::class => function (ContainerInterface $c) {
                $recorder = $c->get(\Maatify\AuthoritativeAudit\Recorder\AuthoritativeAuditRecorder::class);
                $requestContext = $c->get(\App\Context\RequestContext::class);

                assert($recorder instanceof \Maatify\AuthoritativeAudit\Recorder\AuthoritativeAuditRecorder);
                assert($requestContext instanceof \App\Context\RequestContext);

                return new \App\Infrastructure\Logging\AuthoritativeAuditMaatifyAdapter($recorder, $requestContext);
            },

            // 3. Behavior Trace (Operational Activity)
            \Maatify\BehaviorTrace\Contract\BehaviorTraceWriterInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new \Maatify\BehaviorTrace\Infrastructure\Mysql\BehaviorTraceWriterMysqlRepository($pdo);
            },
            \Maatify\BehaviorTrace\Services\ClockInterface::class => function () {
                return new \Maatify\BehaviorTrace\Services\SystemClock();
            },
            \Maatify\BehaviorTrace\Recorder\BehaviorTraceRecorder::class => function (ContainerInterface $c) {
                $writer = $c->get(\Maatify\BehaviorTrace\Contract\BehaviorTraceWriterInterface::class);
                $clock = $c->get(\Maatify\BehaviorTrace\Services\ClockInterface::class);
                $fallbackLogger = $c->get(LoggerInterface::class);

                assert($writer instanceof \Maatify\BehaviorTrace\Contract\BehaviorTraceWriterInterface);
                assert($clock instanceof \Maatify\BehaviorTrace\Services\ClockInterface);

                return new \Maatify\BehaviorTrace\Recorder\BehaviorTraceRecorder($writer, $clock, $fallbackLogger instanceof LoggerInterface ? $fallbackLogger : null);
            },
            \App\Application\Contracts\BehaviorTraceRecorderInterface::class => function (ContainerInterface $c) {
                $recorder = $c->get(\Maatify\BehaviorTrace\Recorder\BehaviorTraceRecorder::class);
                assert($recorder instanceof \Maatify\BehaviorTrace\Recorder\BehaviorTraceRecorder);
                return new \App\Infrastructure\Logging\BehaviorTraceMaatifyAdapter($recorder);
            },

            // 4. Delivery Operations
            \Maatify\DeliveryOperations\Contract\DeliveryOperationsLoggerInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new \Maatify\DeliveryOperations\Infrastructure\Mysql\DeliveryOperationsLoggerMysqlRepository($pdo);
            },
            \Maatify\DeliveryOperations\Services\ClockInterface::class => function () {
                return new \Maatify\DeliveryOperations\Services\SystemClock();
            },
            \Maatify\DeliveryOperations\Recorder\DeliveryOperationsRecorder::class => function (ContainerInterface $c) {
                $logger = $c->get(\Maatify\DeliveryOperations\Contract\DeliveryOperationsLoggerInterface::class);
                $clock = $c->get(\Maatify\DeliveryOperations\Services\ClockInterface::class);
                $fallbackLogger = $c->get(LoggerInterface::class);

                assert($logger instanceof \Maatify\DeliveryOperations\Contract\DeliveryOperationsLoggerInterface);
                assert($clock instanceof \Maatify\DeliveryOperations\Services\ClockInterface);

                return new \Maatify\DeliveryOperations\Recorder\DeliveryOperationsRecorder($logger, $clock, $fallbackLogger instanceof LoggerInterface ? $fallbackLogger : null);
            },
            \App\Application\Contracts\DeliveryOperationsRecorderInterface::class => function (ContainerInterface $c) {
                $recorder = $c->get(\Maatify\DeliveryOperations\Recorder\DeliveryOperationsRecorder::class);
                assert($recorder instanceof \Maatify\DeliveryOperations\Recorder\DeliveryOperationsRecorder);
                return new \App\Infrastructure\Logging\DeliveryOperationsMaatifyAdapter($recorder);
            },

            // 5. Diagnostics Telemetry
            \Maatify\DiagnosticsTelemetry\Contract\DiagnosticsTelemetryLoggerInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new \Maatify\DiagnosticsTelemetry\Infrastructure\Mysql\DiagnosticsTelemetryLoggerMysqlRepository($pdo);
            },
            \Maatify\DiagnosticsTelemetry\Services\ClockInterface::class => function () {
                return new \Maatify\DiagnosticsTelemetry\Services\SystemClock();
            },
            \Maatify\DiagnosticsTelemetry\Recorder\DiagnosticsTelemetryRecorder::class => function (ContainerInterface $c) {
                $logger = $c->get(\Maatify\DiagnosticsTelemetry\Contract\DiagnosticsTelemetryLoggerInterface::class);
                $clock = $c->get(\Maatify\DiagnosticsTelemetry\Services\ClockInterface::class);
                $fallbackLogger = $c->get(LoggerInterface::class);

                assert($logger instanceof \Maatify\DiagnosticsTelemetry\Contract\DiagnosticsTelemetryLoggerInterface);
                assert($clock instanceof \Maatify\DiagnosticsTelemetry\Services\ClockInterface);

                return new \Maatify\DiagnosticsTelemetry\Recorder\DiagnosticsTelemetryRecorder($logger, $clock, $fallbackLogger instanceof LoggerInterface ? $fallbackLogger : null);
            },
            \App\Application\Contracts\DiagnosticsTelemetryRecorderInterface::class => function (ContainerInterface $c) {
                $recorder = $c->get(\Maatify\DiagnosticsTelemetry\Recorder\DiagnosticsTelemetryRecorder::class);
                assert($recorder instanceof \Maatify\DiagnosticsTelemetry\Recorder\DiagnosticsTelemetryRecorder);
                return new \App\Infrastructure\Logging\DiagnosticsTelemetryMaatifyAdapter($recorder);
            },

            // 6. Security Signals
            \Maatify\SecuritySignals\Contract\SecuritySignalsLoggerInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new \Maatify\SecuritySignals\Infrastructure\Mysql\SecuritySignalsLoggerMysqlRepository($pdo);
            },
            \Maatify\SecuritySignals\Services\ClockInterface::class => function () {
                return new \Maatify\SecuritySignals\Services\SystemClock();
            },
            \Maatify\SecuritySignals\Recorder\SecuritySignalsRecorder::class => function (ContainerInterface $c) {
                $logger = $c->get(\Maatify\SecuritySignals\Contract\SecuritySignalsLoggerInterface::class);
                $clock = $c->get(\Maatify\SecuritySignals\Services\ClockInterface::class);
                $fallbackLogger = $c->get(LoggerInterface::class);

                assert($logger instanceof \Maatify\SecuritySignals\Contract\SecuritySignalsLoggerInterface);
                assert($clock instanceof \Maatify\SecuritySignals\Services\ClockInterface);

                return new \Maatify\SecuritySignals\Recorder\SecuritySignalsRecorder($logger, $clock, $fallbackLogger instanceof LoggerInterface ? $fallbackLogger : null);
            },
            \App\Application\Contracts\SecuritySignalsRecorderInterface::class => function (ContainerInterface $c) {
                $recorder = $c->get(\Maatify\SecuritySignals\Recorder\SecuritySignalsRecorder::class);
                assert($recorder instanceof \Maatify\SecuritySignals\Recorder\SecuritySignalsRecorder);
                return new \App\Infrastructure\Logging\SecuritySignalsMaatifyAdapter($recorder);
            },

            PermissionMapperInterface::class => function () {
                return new PermissionMapper();
            }

        ]);

        // Extension Hook: Allow host projects to override/extend bindings
        if ($builderHook !== null) {
            $builderHook($containerBuilder);
        }

        return $containerBuilder->build();
    }
}
