<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Bootstrap;

use DI\ContainerBuilder;
use Exception;
use Maatify\AbuseProtection\Contracts\AbuseDecisionInterface;
use Maatify\AbuseProtection\Contracts\AbuseSignatureProviderInterface;
use Maatify\AbuseProtection\Contracts\ChallengeProviderInterface;
use Maatify\AbuseProtection\Middleware\AbuseProtectionMiddleware;
use Maatify\AbuseProtection\Policy\LoginAbusePolicy;
use Maatify\AbuseProtection\Providers\NullChallengeProvider;
use Maatify\AdminKernel\Application\Admin\AdminProfileUpdateService;
use Maatify\AdminKernel\Application\Auth\AdminLoginService;
use Maatify\AdminKernel\Application\Auth\AdminLogoutService;
use Maatify\AdminKernel\Application\Crypto\AdminIdentifierCryptoServiceInterface;
use Maatify\AdminKernel\Application\Crypto\NotificationCryptoServiceInterface;
use Maatify\AdminKernel\Application\Crypto\PasswordCryptoServiceInterface;
use Maatify\AdminKernel\Application\Crypto\TotpSecretCryptoServiceInterface;
use Maatify\AdminKernel\Application\Verification\VerificationNotificationDispatcher;
use Maatify\AdminKernel\Application\Verification\VerificationNotificationDispatcherInterface;
use Maatify\AdminKernel\Context\ActorContext;
use Maatify\AdminKernel\Domain\Admin\Reader\AdminBasicInfoReaderInterface;
use Maatify\AdminKernel\Domain\Admin\Reader\AdminEmailReaderInterface;
use Maatify\AdminKernel\Domain\Admin\Reader\AdminProfileReaderInterface;
use Maatify\AdminKernel\Domain\Admin\Reader\AdminQueryReaderInterface;
use Maatify\AdminKernel\Domain\Contracts\Abuse\AbuseCookieServiceInterface;
use Maatify\AdminKernel\Domain\Contracts\Abuse\ChallengeWidgetRendererInterface;
use Maatify\AdminKernel\Domain\Contracts\ActorProviderInterface;
use Maatify\AdminKernel\Domain\Contracts\AdminDirectPermissionRepositoryInterface;
use Maatify\AdminKernel\Domain\Contracts\AdminEmailVerificationRepositoryInterface;
use Maatify\AdminKernel\Domain\Contracts\AdminIdentifierLookupInterface;
use Maatify\AdminKernel\Domain\Contracts\AdminNotificationChannelRepositoryInterface;
use Maatify\AdminKernel\Domain\Contracts\AdminNotificationHistoryReaderInterface;
use Maatify\AdminKernel\Domain\Contracts\AdminNotificationPersistenceWriterInterface;
use Maatify\AdminKernel\Domain\Contracts\AdminNotificationPreferenceReaderInterface;
use Maatify\AdminKernel\Domain\Contracts\AdminNotificationPreferenceRepositoryInterface;
use Maatify\AdminKernel\Domain\Contracts\AdminNotificationPreferenceWriterInterface;
use Maatify\AdminKernel\Domain\Contracts\AdminNotificationReadMarkerInterface;
use Maatify\AdminKernel\Domain\Contracts\AdminPasswordRepositoryInterface;
use Maatify\AdminKernel\Domain\Contracts\AdminRoleRepositoryInterface;
use Maatify\AdminKernel\Domain\Contracts\AdminSessionRepositoryInterface;
use Maatify\AdminKernel\Domain\Contracts\AdminTotpSecretRepositoryInterface;
use Maatify\AdminKernel\Domain\Contracts\AdminTotpSecretStoreInterface;
use Maatify\AdminKernel\Domain\Contracts\FailedNotificationRepositoryInterface;
use Maatify\AdminKernel\Domain\Contracts\NotificationReadRepositoryInterface;
use Maatify\AdminKernel\Domain\Contracts\NotificationRoutingInterface;
use Maatify\AdminKernel\Domain\Contracts\PermissionMapperInterface;
use Maatify\AdminKernel\Domain\Contracts\Permissions\DirectPermissionsWriterRepositoryInterface;
use Maatify\AdminKernel\Domain\Contracts\PermissionsMetadataRepositoryInterface;
use Maatify\AdminKernel\Domain\Contracts\PermissionsReaderRepositoryInterface;
use Maatify\AdminKernel\Domain\Contracts\RememberMeRepositoryInterface;
use Maatify\AdminKernel\Domain\Contracts\RolePermissionRepositoryInterface;
use Maatify\AdminKernel\Domain\Contracts\Roles\RoleAdminsRepositoryInterface;
use Maatify\AdminKernel\Domain\Contracts\Roles\RoleCreateRepositoryInterface;
use Maatify\AdminKernel\Domain\Contracts\Roles\RolePermissionsRepositoryInterface;
use Maatify\AdminKernel\Domain\Contracts\Roles\RoleRenameRepositoryInterface;
use Maatify\AdminKernel\Domain\Contracts\Roles\RoleRepositoryInterface;
use Maatify\AdminKernel\Domain\Contracts\Roles\RolesMetadataRepositoryInterface;
use Maatify\AdminKernel\Domain\Contracts\Roles\RolesReaderRepositoryInterface;
use Maatify\AdminKernel\Domain\Contracts\Roles\RoleToggleRepositoryInterface;
use Maatify\AdminKernel\Domain\Contracts\StepUpGrantRepositoryInterface;
use Maatify\AdminKernel\Domain\Contracts\TotpServiceInterface;
use Maatify\AdminKernel\Domain\Contracts\VerificationCodeGeneratorInterface;
use Maatify\AdminKernel\Domain\Contracts\VerificationCodePolicyResolverInterface;
use Maatify\AdminKernel\Domain\Contracts\VerificationCodeRepositoryInterface;
use Maatify\AdminKernel\Domain\Contracts\VerificationCodeValidatorInterface;
use Maatify\AdminKernel\Domain\DTO\AdminConfigDTO;
use Maatify\AdminKernel\Domain\DTO\TotpEnrollmentConfig;
use Maatify\AdminKernel\Domain\DTO\Ui\UiConfigDTO;
use Maatify\AdminKernel\Domain\I18n\Reader\LanguageQueryReaderInterface;
use Maatify\AdminKernel\Domain\Ownership\SystemOwnershipRepositoryInterface;
use Maatify\AdminKernel\Domain\Security\Crypto\AdminCryptoContextProvider;
use Maatify\AdminKernel\Domain\Security\Crypto\CryptoKeyRingConfig;
use Maatify\AdminKernel\Domain\Security\Password\PasswordPepperRing;
use Maatify\AdminKernel\Domain\Security\Password\PasswordPepperRingConfig;
use Maatify\AdminKernel\Domain\Security\PermissionMapper;
use Maatify\AdminKernel\Domain\Service\AdminAuthenticationService;
use Maatify\AdminKernel\Domain\Service\AdminEmailVerificationService;
use Maatify\AdminKernel\Domain\Service\AdminNotificationRoutingService;
use Maatify\AdminKernel\Domain\Service\AuthorizationService;
use Maatify\AdminKernel\Domain\Service\PasswordService;
use Maatify\AdminKernel\Domain\Service\RecoveryStateService;
use Maatify\AdminKernel\Domain\Service\RememberMeService;
use Maatify\AdminKernel\Domain\Service\RoleAssignmentService;
use Maatify\AdminKernel\Domain\Service\RoleHierarchyComparator;
use Maatify\AdminKernel\Domain\Service\RoleLevelResolver;
use Maatify\AdminKernel\Domain\Service\SessionRevocationService;
use Maatify\AdminKernel\Domain\Service\StepUpService;
use Maatify\AdminKernel\Domain\Service\VerificationCodeGenerator;
use Maatify\AdminKernel\Domain\Service\VerificationCodePolicyResolver;
use Maatify\AdminKernel\Domain\Service\VerificationCodeValidator;
use Maatify\AdminKernel\Domain\Session\Reader\SessionListReaderInterface;
use Maatify\AdminKernel\Http\Controllers\AdminController;
use Maatify\AdminKernel\Http\Controllers\AdminEmailVerificationController;
use Maatify\AdminKernel\Http\Controllers\AdminNotificationHistoryController;
use Maatify\AdminKernel\Http\Controllers\AdminNotificationPreferenceController;
use Maatify\AdminKernel\Http\Controllers\AdminNotificationReadController;
use Maatify\AdminKernel\Http\Controllers\Api\AdminQueryController;
use Maatify\AdminKernel\Http\Controllers\Api\PermissionMetadataUpdateController;
use Maatify\AdminKernel\Http\Controllers\Api\PermissionsController;
use Maatify\AdminKernel\Http\Controllers\Api\Roles\RoleCreateController;
use Maatify\AdminKernel\Http\Controllers\Api\Roles\RoleMetadataUpdateController;
use Maatify\AdminKernel\Http\Controllers\Api\Roles\RoleRenameController;
use Maatify\AdminKernel\Http\Controllers\Api\Roles\RolesControllerQuery;
use Maatify\AdminKernel\Http\Controllers\Api\Roles\RoleToggleController;
use Maatify\AdminKernel\Http\Controllers\Api\SessionBulkRevokeController;
use Maatify\AdminKernel\Http\Controllers\Api\SessionQueryController;
use Maatify\AdminKernel\Http\Controllers\Api\SessionRevokeController;
use Maatify\AdminKernel\Http\Controllers\AuthController;
use Maatify\AdminKernel\Http\Controllers\NotificationQueryController;
use Maatify\AdminKernel\Http\Controllers\TelegramWebhookController;
use Maatify\AdminKernel\Http\Controllers\Ui\SessionListController;
use Maatify\AdminKernel\Http\Controllers\Ui\UiAdminsController;
use Maatify\AdminKernel\Http\Controllers\Ui\UiDashboardController;
use Maatify\AdminKernel\Http\Controllers\Ui\UiPermissionsController;
use Maatify\AdminKernel\Http\Controllers\Ui\UiRolesController;
use Maatify\AdminKernel\Http\Controllers\Ui\UiSettingsController;
use Maatify\AdminKernel\Http\Controllers\Web\DashboardController;
use Maatify\AdminKernel\Http\Controllers\Web\EmailVerificationController;
use Maatify\AdminKernel\Http\Controllers\Web\LoginController;
use Maatify\AdminKernel\Http\Controllers\Web\LogoutController;
use Maatify\AdminKernel\Http\Controllers\Web\TelegramConnectController;
use Maatify\AdminKernel\Http\Controllers\Web\TwoFactorController;
use Maatify\AdminKernel\Http\Middleware\RecoveryStateMiddleware;
use Maatify\AdminKernel\Http\Middleware\RememberMeMiddleware;
use Maatify\AdminKernel\Http\Middleware\ScopeGuardMiddleware;
use Maatify\AdminKernel\Http\Middleware\SessionStateGuardMiddleware;
use Maatify\AdminKernel\Infrastructure\Admin\Reader\PDOAdminBasicInfoReader;
use Maatify\AdminKernel\Infrastructure\Admin\Reader\PDOAdminEmailReader;
use Maatify\AdminKernel\Infrastructure\Admin\Reader\PdoAdminProfileReader;
use Maatify\AdminKernel\Infrastructure\Context\ActorContextProvider;
use Maatify\AdminKernel\Infrastructure\Crypto\AdminIdentifierCryptoService;
use Maatify\AdminKernel\Infrastructure\Crypto\NotificationCryptoService;
use Maatify\AdminKernel\Infrastructure\Crypto\PasswordCryptoService;
use Maatify\AdminKernel\Infrastructure\Crypto\TotpSecretCryptoService;
use Maatify\AdminKernel\Infrastructure\Database\PDOFactory;
use Maatify\AdminKernel\Infrastructure\Notification\TelegramHandler;
use Maatify\AdminKernel\Infrastructure\Query\ListFilterResolver;
use Maatify\AdminKernel\Infrastructure\Reader\Admin\PdoAdminQueryReader;
use Maatify\AdminKernel\Infrastructure\Reader\I18n\PdoLanguageQueryReader;
use Maatify\AdminKernel\Infrastructure\Reader\PDOPermissionsReaderRepository;
use Maatify\AdminKernel\Infrastructure\Reader\PDORolesReaderRepository;
use Maatify\AdminKernel\Infrastructure\Reader\Session\PdoSessionListReader;
use Maatify\AdminKernel\Infrastructure\Repository\AdminEmailRepository;
use Maatify\AdminKernel\Infrastructure\Repository\AdminNotificationChannelRepository;
use Maatify\AdminKernel\Infrastructure\Repository\AdminPasswordRepository;
use Maatify\AdminKernel\Infrastructure\Repository\AdminRepository;
use Maatify\AdminKernel\Infrastructure\Repository\AdminRoleRepository;
use Maatify\AdminKernel\Infrastructure\Repository\AdminSessionRepository;
use Maatify\AdminKernel\Infrastructure\Repository\AdminTotpSecretRepository;
use Maatify\AdminKernel\Infrastructure\Repository\FailedNotificationRepository;
use Maatify\AdminKernel\Infrastructure\Repository\NotificationReadRepository;
use Maatify\AdminKernel\Infrastructure\Repository\PdoAdminDirectPermissionRepository;
use Maatify\AdminKernel\Infrastructure\Repository\PdoAdminNotificationHistoryReader;
use Maatify\AdminKernel\Infrastructure\Repository\PdoAdminNotificationPersistenceRepository;
use Maatify\AdminKernel\Infrastructure\Repository\PdoAdminNotificationPreferenceRepository;
use Maatify\AdminKernel\Infrastructure\Repository\PdoAdminNotificationReadMarker;
use Maatify\AdminKernel\Infrastructure\Repository\PdoRememberMeRepository;
use Maatify\AdminKernel\Infrastructure\Repository\PdoStepUpGrantRepository;
use Maatify\AdminKernel\Infrastructure\Repository\PdoSystemOwnershipRepository;
use Maatify\AdminKernel\Infrastructure\Repository\PdoVerificationCodeRepository;
use Maatify\AdminKernel\Infrastructure\Repository\RolePermissionRepository;
use Maatify\AdminKernel\Infrastructure\Repository\Roles\PdoRoleAdminsRepository;
use Maatify\AdminKernel\Infrastructure\Repository\Roles\PdoRoleCreateRepository;
use Maatify\AdminKernel\Infrastructure\Repository\Roles\PdoRolePermissionsRepository;
use Maatify\AdminKernel\Infrastructure\Repository\Roles\PdoRoleRepository;
use Maatify\AdminKernel\Infrastructure\Security\Abuse\AbuseCookieService;
use Maatify\AdminKernel\Infrastructure\Security\Abuse\Enums\AbuseChallengeProviderEnum;
use Maatify\AdminKernel\Infrastructure\Security\Abuse\HCaptchaChallengeProvider;
use Maatify\AdminKernel\Infrastructure\Security\Abuse\HCaptchaConfigDTO;
use Maatify\AdminKernel\Infrastructure\Security\Abuse\HCaptchaWidgetRenderer;
use Maatify\AdminKernel\Infrastructure\Security\Abuse\NullChallengeWidgetRenderer;
use Maatify\AdminKernel\Infrastructure\Security\Abuse\RecaptchaV2ChallengeProvider;
use Maatify\AdminKernel\Infrastructure\Security\Abuse\RecaptchaV2ConfigDTO;
use Maatify\AdminKernel\Infrastructure\Security\Abuse\RecaptchaV2WidgetRenderer;
use Maatify\AdminKernel\Infrastructure\Security\Abuse\TurnstileChallengeProvider;
use Maatify\AdminKernel\Infrastructure\Security\Abuse\TurnstileConfigDTO;
use Maatify\AdminKernel\Infrastructure\Security\Abuse\TurnstileWidgetRenderer;
use Maatify\AdminKernel\Infrastructure\Service\AdminTotpSecretStore;
use Maatify\AdminKernel\Infrastructure\Service\Google2faTotpService;
use Maatify\AdminKernel\Infrastructure\Updater\PDOPermissionsMetadataRepository;
use Maatify\AdminKernel\Kernel\Adapter\CryptoKeyRingEnvAdapter;
use Maatify\AdminKernel\Kernel\Adapter\PasswordPepperEnvAdapter;
use Maatify\AdminKernel\Kernel\DTO\AdminRuntimeConfigDTO;
use Maatify\Crypto\Contract\CryptoContextProviderInterface;
use Maatify\Crypto\DX\CryptoContextFactory;
use Maatify\Crypto\DX\CryptoDirectFactory;
use Maatify\Crypto\DX\CryptoProvider;
use Maatify\Crypto\HKDF\HKDFService;
use Maatify\Crypto\KeyRotation\DTO\CryptoKeyDTO;
use Maatify\Crypto\KeyRotation\KeyRotationService;
use Maatify\Crypto\KeyRotation\KeyStatusEnum;
use Maatify\Crypto\KeyRotation\Policy\StrictSingleActiveKeyPolicy;
use Maatify\Crypto\KeyRotation\Providers\InMemoryKeyProvider;
use Maatify\Crypto\Reversible\Algorithms\Aes256GcmAlgorithm;
use Maatify\Crypto\Reversible\Registry\ReversibleCryptoAlgorithmRegistry;
use Maatify\EmailDelivery\Config\EmailTransportConfigDTO;
use Maatify\EmailDelivery\Queue\EmailQueueWriterInterface;
use Maatify\EmailDelivery\Queue\PdoEmailQueueWriter;
use Maatify\EmailDelivery\Renderer\EmailRendererInterface;
use Maatify\EmailDelivery\Renderer\TwigEmailRenderer;
use Maatify\EmailDelivery\Transport\EmailTransportInterface;
use Maatify\EmailDelivery\Transport\SmtpEmailTransport;
use Maatify\InputNormalization\Contracts\InputNormalizerInterface;
use Maatify\InputNormalization\Middleware\InputNormalizationMiddleware;
use Maatify\InputNormalization\Normalizer\InputNormalizer;
use Maatify\PsrLogger\LoggerFactory;
use Maatify\SharedCommon\Contracts\ClockInterface;
use Maatify\Validation\Contracts\ValidatorInterface;
use Maatify\Validation\Guard\ValidationGuard;
use Maatify\Validation\Validator\RespectValidator;
use PDO;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
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
        ?callable $builderHook = null,
        ?string $templatesPath = null,
        ?string $assetsBaseUrl = null
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

        $abuseProvider = AbuseChallengeProviderEnum::tryFrom(
            $runtime->abuseChallengeProvider ?? 'none'
        ) ?? throw new RuntimeException(
            'Invalid ABUSE_CHALLENGE_PROVIDER value'
        );

        $turnstileConfigDTO = new TurnstileConfigDTO(
            siteKey: $runtime->turnstileSiteKey,
            secretKey: $runtime->turnstileSecretKey
        );

        // Currently bound to login flow only.
        // Extendable when AbusePolicy supports multi-context challenges.
        $hCaptchaConfigDTO = new HCaptchaConfigDto(
            siteKey: $runtime->hCaptchaSiteKey,
            secretKey: $runtime->hCaptchaSecretKey
        );

        $recaptchaV2ConfigDTO = new RecaptchaV2ConfigDto(
            siteKey: $runtime->recaptchaV2SiteKey,
            secretKey: $runtime->recaptchaV2SecretKey
        );

        // Enforce Timezone
        // date_default_timezone_set($config->timezone); // Removed in Kernelization Step 1(B)

        $containerBuilder->addDefinitions([
            \Maatify\SharedCommon\Contracts\ClockInterface::class => function () use ($config) {
                try {
                    $timezone = new \DateTimeZone($config->timezone);
                } catch (\Exception $e) {
                    throw new \RuntimeException("Invalid APP_TIMEZONE: " . $config->timezone, 0, $e);
                }
                return new \Maatify\SharedCommon\Infrastructure\SystemClock($timezone);
            },
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
            TurnstileConfigDTO::class => function () use ($turnstileConfigDTO) {
                return $turnstileConfigDTO;
            },
            HCaptchaConfigDto::class => function () use ($hCaptchaConfigDTO) {
                return $hCaptchaConfigDTO;
            },
            RecaptchaV2ConfigDto::class => function () use ($recaptchaV2ConfigDTO) {
                return $recaptchaV2ConfigDTO;
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
            \Maatify\AdminKernel\Domain\Contracts\Ui\NavigationProviderInterface::class => function (ContainerInterface $c) {
                return new \Maatify\AdminKernel\Infrastructure\Ui\DefaultNavigationProvider();
            },
            Twig::class                                               => function (ContainerInterface $c) use ($templatesPath, $assetsBaseUrl) {
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
                $kernelPath = $templatesPath ?? (__DIR__ . '/../Templates');
                $loader->addPath($kernelPath);          // Main namespace (fallback)
                $loader->addPath($kernelPath, 'admin'); // @admin namespace

                $twig = new Twig($loader, ['cache' => false]);

                $navProvider = $c->get(\Maatify\AdminKernel\Domain\Contracts\Ui\NavigationProviderInterface::class);
                assert($navProvider instanceof \Maatify\AdminKernel\Domain\Contracts\Ui\NavigationProviderInterface);

                $twig->getEnvironment()->addGlobal('nav_items', $navProvider->getNavigationItems());
                $twig->getEnvironment()->addGlobal('ui', $uiConfigDTO);

                $twig->getEnvironment()->addFunction(new \Twig\TwigFunction('asset', function (string $path) use ($uiConfigDTO, $assetsBaseUrl): string {
                    $base = $assetsBaseUrl ?? $uiConfigDTO->adminAssetBaseUrl;
                    return rtrim($base, '/') . '/' . ltrim($path, '/');
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
            CryptoContextProviderInterface::class => function (ContainerInterface $c) {
                return new AdminCryptoContextProvider();
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

                $passwordRepository = $c->get(\Maatify\AdminKernel\Domain\Contracts\AdminPasswordRepositoryInterface::class);
                $passwordService = $c->get(\Maatify\AdminKernel\Domain\Service\PasswordService::class);
                $pdo = $c->get(PDO::class);

                $emailReader = $c->get(AdminEmailReaderInterface::class);
                $basicInfoReader = $c->get(AdminBasicInfoReaderInterface::class);

                assert($adminRepo instanceof AdminRepository);
                assert($emailRepo instanceof AdminEmailRepository);
                assert($validationGuard instanceof ValidationGuard);
                assert($cryptoService instanceof AdminIdentifierCryptoServiceInterface);

                assert($passwordRepository instanceof \Maatify\AdminKernel\Domain\Contracts\AdminPasswordRepositoryInterface);
                assert($passwordService instanceof \Maatify\AdminKernel\Domain\Service\PasswordService);
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
            AdminEmailVerificationService::class                      => function (ContainerInterface $c) {
                $repo = $c->get(AdminEmailVerificationRepositoryInterface::class);
                $pdo = $c->get(PDO::class);
                $clock = $c->get(\Maatify\SharedCommon\Contracts\ClockInterface::class);

                assert($repo instanceof AdminEmailVerificationRepositoryInterface);
                assert($pdo instanceof PDO);
                assert($clock instanceof \Maatify\SharedCommon\Contracts\ClockInterface);

                return new AdminEmailVerificationService($repo, $pdo, $clock);
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
            \Maatify\AdminKernel\Domain\Service\SessionValidationService::class       => function (ContainerInterface $c) {
                $repo = $c->get(\Maatify\AdminKernel\Domain\Contracts\AdminSessionValidationRepositoryInterface::class);
                $clock = $c->get(\Maatify\SharedCommon\Contracts\ClockInterface::class);
                assert($repo instanceof \Maatify\AdminKernel\Domain\Contracts\AdminSessionValidationRepositoryInterface);
                assert($clock instanceof \Maatify\SharedCommon\Contracts\ClockInterface);
                return new \Maatify\AdminKernel\Domain\Service\SessionValidationService($repo, $clock);
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
            AdminSessionRepositoryInterface::class                    => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                $clock = $c->get(\Maatify\SharedCommon\Contracts\ClockInterface::class);
                assert($pdo instanceof PDO);
                assert($clock instanceof \Maatify\SharedCommon\Contracts\ClockInterface);
                return new AdminSessionRepository($pdo, $clock);
            },
            \Maatify\AdminKernel\Domain\Contracts\AdminSessionValidationRepositoryInterface::class => function (ContainerInterface $c) {
                return $c->get(AdminSessionRepositoryInterface::class);
            },
            SessionRevocationService::class => function (ContainerInterface $c) {
                $repo = $c->get(\Maatify\AdminKernel\Domain\Contracts\AdminSessionValidationRepositoryInterface::class);
                $pdo = $c->get(PDO::class);

                assert($repo instanceof \Maatify\AdminKernel\Domain\Contracts\AdminSessionValidationRepositoryInterface);
                assert($pdo instanceof PDO);

                return new SessionRevocationService($repo, $pdo);
            },
            RememberMeRepositoryInterface::class                      => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                $clock = $c->get(\Maatify\SharedCommon\Contracts\ClockInterface::class);
                assert($pdo instanceof PDO);
                assert($clock instanceof \Maatify\SharedCommon\Contracts\ClockInterface);
                return new PdoRememberMeRepository($pdo, $clock);
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
            AdminLoginService::class                                  => function (ContainerInterface $c) {
                $authService = $c->get(AdminAuthenticationService::class);
                $sessionRepo = $c->get(\Maatify\AdminKernel\Domain\Contracts\AdminSessionValidationRepositoryInterface::class);
                $rememberMeService = $c->get(RememberMeService::class);
                $cryptoService = $c->get(AdminIdentifierCryptoServiceInterface::class);
                $clock = $c->get(\Maatify\SharedCommon\Contracts\ClockInterface::class);
                $abuseCookieService = $c->get(AbuseCookieServiceInterface::class);
                assert($authService instanceof AdminAuthenticationService);
                assert($sessionRepo instanceof \Maatify\AdminKernel\Domain\Contracts\AdminSessionValidationRepositoryInterface);
                assert($rememberMeService instanceof RememberMeService);
                assert($cryptoService instanceof AdminIdentifierCryptoServiceInterface);
                assert($clock instanceof \Maatify\SharedCommon\Contracts\ClockInterface);
                assert($abuseCookieService instanceof AbuseCookieServiceInterface);

                return new AdminLoginService($authService, $sessionRepo, $rememberMeService, $cryptoService, $clock, $abuseCookieService);
            },
            LoginController::class => function (ContainerInterface $c) {
                $adminLoginService = $c->get(AdminLoginService::class);
                $view = $c->get(Twig::class);
                $challengeRenderer = $c->get(ChallengeWidgetRendererInterface::class);
                assert($adminLoginService instanceof AdminLoginService);
                assert($view instanceof Twig);
                assert($challengeRenderer instanceof ChallengeWidgetRendererInterface);

                return new LoginController(
                    $adminLoginService,
                    $view,
                    $challengeRenderer
                );
            },
            \Maatify\AdminKernel\Application\Auth\AdminLogoutService::class => function (ContainerInterface $c) {
                $sessionRepo = $c->get(\Maatify\AdminKernel\Domain\Contracts\AdminSessionValidationRepositoryInterface::class);
                $rememberMeService = $c->get(RememberMeService::class);
                $authService = $c->get(AdminAuthenticationService::class);

                // Telemetry
                $telemetryService = $c->get(\Maatify\AdminKernel\Application\Services\DiagnosticsTelemetryService::class);

                assert($sessionRepo instanceof \Maatify\AdminKernel\Domain\Contracts\AdminSessionValidationRepositoryInterface);
                assert($rememberMeService instanceof RememberMeService);
                assert($authService instanceof AdminAuthenticationService);
                assert($telemetryService instanceof \Maatify\AdminKernel\Application\Services\DiagnosticsTelemetryService);

                return new AdminLogoutService($sessionRepo, $rememberMeService, $authService, $telemetryService);
            },
            LogoutController::class => function (ContainerInterface $c) {
                $adminLogoutService = $c->get(\Maatify\AdminKernel\Application\Auth\AdminLogoutService::class);
                assert($adminLogoutService instanceof \Maatify\AdminKernel\Application\Auth\AdminLogoutService);

                return new LogoutController($adminLogoutService);
            },
            \Maatify\AdminKernel\Application\Auth\ChangePasswordService::class => function (ContainerInterface $c) {
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

                return new \Maatify\AdminKernel\Application\Auth\ChangePasswordService(
                    $cryptoService,
                    $identifierLookup,
                    $passwordRepo,
                    $passwordService,
                    $recoveryState,
                    $pdo
                );
            },
            \Maatify\AdminKernel\Http\Controllers\Web\ChangePasswordController::class => function (ContainerInterface $c) {
                $view = $c->get(Twig::class);
                $changePasswordService = $c->get(\Maatify\AdminKernel\Application\Auth\ChangePasswordService::class);

                assert($view instanceof Twig);
                assert($changePasswordService instanceof \Maatify\AdminKernel\Application\Auth\ChangePasswordService);

                return new \Maatify\AdminKernel\Http\Controllers\Web\ChangePasswordController($view, $changePasswordService);
            },
            \Maatify\AdminKernel\Application\Auth\VerifyEmailService::class => function (ContainerInterface $c) {
                $cryptoService = $c->get(AdminIdentifierCryptoServiceInterface::class);
                $lookupInterface = $c->get(AdminIdentifierLookupInterface::class);
                $validator = $c->get(VerificationCodeValidatorInterface::class);
                $verificationService = $c->get(AdminEmailVerificationService::class);

                assert($cryptoService instanceof AdminIdentifierCryptoServiceInterface);
                assert($lookupInterface instanceof AdminIdentifierLookupInterface);
                assert($validator instanceof VerificationCodeValidatorInterface);
                assert($verificationService instanceof AdminEmailVerificationService);

                return new \Maatify\AdminKernel\Application\Auth\VerifyEmailService(
                    $cryptoService,
                    $lookupInterface,
                    $validator,
                    $verificationService
                );
            },

            \Maatify\AdminKernel\Application\Auth\ResendEmailVerificationService::class => function (ContainerInterface $c) {
                $cryptoService = $c->get(AdminIdentifierCryptoServiceInterface::class);
                $lookupInterface = $c->get(AdminIdentifierLookupInterface::class);
                $generator = $c->get(VerificationCodeGeneratorInterface::class);
                $dispatcher = $c->get(VerificationNotificationDispatcherInterface::class);

                assert($cryptoService instanceof AdminIdentifierCryptoServiceInterface);
                assert($lookupInterface instanceof AdminIdentifierLookupInterface);
                assert($generator instanceof VerificationCodeGeneratorInterface);
                assert($dispatcher instanceof VerificationNotificationDispatcherInterface);

                return new \Maatify\AdminKernel\Application\Auth\ResendEmailVerificationService(
                    $cryptoService,
                    $lookupInterface,
                    $generator,
                    $dispatcher
                );
            },
            EmailVerificationController::class => function (ContainerInterface $c) {

                $view = $c->get(Twig::class);
                $verifyEmailService = $c->get(\Maatify\AdminKernel\Application\Auth\VerifyEmailService::class);
                $resendEmailVerificationService = $c->get(\Maatify\AdminKernel\Application\Auth\ResendEmailVerificationService::class);

                assert($view instanceof Twig);
                assert($verifyEmailService instanceof \Maatify\AdminKernel\Application\Auth\VerifyEmailService);
                assert($resendEmailVerificationService instanceof \Maatify\AdminKernel\Application\Auth\ResendEmailVerificationService);

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
                $authorizationService = $c->get(AuthorizationService::class);
                assert($view instanceof Twig);
                assert($profileReader instanceof AdminProfileReaderInterface);
                assert($profileUpdateService instanceof AdminProfileUpdateService);
                assert($emailReaderInterface instanceof AdminEmailReaderInterface);
                assert($basicInfoReaderInterface instanceof AdminBasicInfoReaderInterface);
                assert($authorizationService instanceof AuthorizationService);
                return new UiAdminsController(
                    $view,
                    $profileReader,
                    $profileUpdateService,
                    $emailReaderInterface,
                    $basicInfoReaderInterface,
                    $authorizationService,
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
            \Maatify\AdminKernel\Application\Auth\TwoFactorEnrollmentService::class => function (ContainerInterface $c) {
                $stepUpService = $c->get(StepUpService::class);
                $totpService = $c->get(TotpServiceInterface::class);
                $telemetryService = $c->get(\Maatify\AdminKernel\Application\Services\DiagnosticsTelemetryService::class);

                assert($stepUpService instanceof StepUpService);
                assert($totpService instanceof TotpServiceInterface);
                assert($telemetryService instanceof \Maatify\AdminKernel\Application\Services\DiagnosticsTelemetryService);

                return new \Maatify\AdminKernel\Application\Auth\TwoFactorEnrollmentService($stepUpService, $totpService, $telemetryService);
            },
            \Maatify\AdminKernel\Application\Auth\TwoFactorVerificationService::class => function (ContainerInterface $c) {
                $stepUpService = $c->get(StepUpService::class);
                $telemetryService = $c->get(\Maatify\AdminKernel\Application\Services\DiagnosticsTelemetryService::class);

                assert($stepUpService instanceof StepUpService);
                assert($telemetryService instanceof \Maatify\AdminKernel\Application\Services\DiagnosticsTelemetryService);

                return new \Maatify\AdminKernel\Application\Auth\TwoFactorVerificationService($stepUpService, $telemetryService);

            },
            TwoFactorController::class => function (ContainerInterface $c) {
                $enrollmentService = $c->get(\Maatify\AdminKernel\Application\Auth\TwoFactorEnrollmentService::class);
                $verificationService = $c->get(\Maatify\AdminKernel\Application\Auth\TwoFactorVerificationService::class);
                $view = $c->get(Twig::class);

                assert($enrollmentService instanceof \Maatify\AdminKernel\Application\Auth\TwoFactorEnrollmentService);
                assert($verificationService instanceof \Maatify\AdminKernel\Application\Auth\TwoFactorVerificationService);
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
                $authorizationService = $c->get(AuthorizationService::class);
                assert($twig instanceof Twig);
                assert($authorizationService instanceof AuthorizationService);
                return new SessionListController($twig, $authorizationService);
            },
            SessionQueryController::class => function (ContainerInterface $c) {
                $reader = $c->get(SessionListReaderInterface::class);
                $auth = $c->get(AuthorizationService::class);
                $validationGuard = $c->get(ValidationGuard::class);
                $filterResolver = $c->get(\Maatify\AdminKernel\Infrastructure\Query\ListFilterResolver::class);
                $telemetryService = $c->get(\Maatify\AdminKernel\Application\Services\DiagnosticsTelemetryService::class);

                assert($reader instanceof SessionListReaderInterface);
                assert($auth instanceof AuthorizationService);
                assert($validationGuard instanceof ValidationGuard);
                assert($filterResolver instanceof \Maatify\AdminKernel\Infrastructure\Query\ListFilterResolver);
                assert($telemetryService instanceof \Maatify\AdminKernel\Application\Services\DiagnosticsTelemetryService);


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
            StepUpGrantRepositoryInterface::class                     => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                $clock = $c->get(\Maatify\SharedCommon\Contracts\ClockInterface::class);
                assert($pdo instanceof PDO);
                assert($clock instanceof \Maatify\SharedCommon\Contracts\ClockInterface);
                return new PdoStepUpGrantRepository($pdo, $clock);
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
            StepUpService::class                                      => function (ContainerInterface $c) {
                $grantRepo = $c->get(StepUpGrantRepositoryInterface::class);
                $totpSecretStore = $c->get(AdminTotpSecretStoreInterface::class);
                $totpService = $c->get(TotpServiceInterface::class);
                $recoveryState = $c->get(RecoveryStateService::class);
                $pdo = $c->get(PDO::class);
                $clock = $c->get(\Maatify\SharedCommon\Contracts\ClockInterface::class);

                assert($grantRepo instanceof StepUpGrantRepositoryInterface);
                assert($totpSecretStore instanceof AdminTotpSecretStoreInterface);
                assert($totpService instanceof TotpServiceInterface);
                assert($recoveryState instanceof RecoveryStateService);
                assert($pdo instanceof PDO);
                assert($clock instanceof \Maatify\SharedCommon\Contracts\ClockInterface);

                return new StepUpService(
                    $grantRepo,
                    $totpSecretStore,
                    $totpService,
                    $recoveryState,
                    $pdo,
                    $clock
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
            \Maatify\AdminKernel\Http\Controllers\StepUpController::class => function (ContainerInterface $c) {
                $stepUpService = $c->get(\Maatify\AdminKernel\Domain\Service\StepUpService::class);
                $validationGuard = $c->get(\Maatify\Validation\Guard\ValidationGuard::class);
                $telemetryService = $c->get(\Maatify\AdminKernel\Application\Services\DiagnosticsTelemetryService::class);

                assert($stepUpService instanceof \Maatify\AdminKernel\Domain\Service\StepUpService);
                assert($validationGuard instanceof \Maatify\Validation\Guard\ValidationGuard);
                assert($telemetryService instanceof \Maatify\AdminKernel\Application\Services\DiagnosticsTelemetryService);

                return new \Maatify\AdminKernel\Http\Controllers\StepUpController(
                    $stepUpService,
                    $validationGuard,
                    $telemetryService
                );
            },

            // Phase Sx: Verification Code Infrastructure
            VerificationCodeRepositoryInterface::class                => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                $clock = $c->get(\Maatify\SharedCommon\Contracts\ClockInterface::class);
                assert($pdo instanceof PDO);
                assert($clock instanceof \Maatify\SharedCommon\Contracts\ClockInterface);
                return new PdoVerificationCodeRepository($pdo, $clock);
            },
            VerificationCodePolicyResolverInterface::class => function (ContainerInterface $c) {
                return new VerificationCodePolicyResolver();
            },
            VerificationCodeGeneratorInterface::class                 => function (ContainerInterface $c) {
                $repo = $c->get(VerificationCodeRepositoryInterface::class);
                $resolver = $c->get(VerificationCodePolicyResolverInterface::class);
                $clock = $c->get(\Maatify\SharedCommon\Contracts\ClockInterface::class);
                assert($repo instanceof VerificationCodeRepositoryInterface);
                assert($resolver instanceof VerificationCodePolicyResolverInterface);
                assert($clock instanceof \Maatify\SharedCommon\Contracts\ClockInterface);
                return new VerificationCodeGenerator($repo, $resolver, $clock);
            },
            VerificationCodeValidatorInterface::class                 => function (ContainerInterface $c) {
                $repo = $c->get(VerificationCodeRepositoryInterface::class);
                $clock = $c->get(\Maatify\SharedCommon\Contracts\ClockInterface::class);
                assert($repo instanceof VerificationCodeRepositoryInterface);
                assert($clock instanceof \Maatify\SharedCommon\Contracts\ClockInterface);
                return new VerificationCodeValidator($repo, $clock);
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
            RememberMeService::class                                  => function (ContainerInterface $c) {
                $rememberMeRepo = $c->get(RememberMeRepositoryInterface::class);
                $sessionRepo = $c->get(AdminSessionRepositoryInterface::class);
                $pdo = $c->get(PDO::class);
                $clock = $c->get(\Maatify\SharedCommon\Contracts\ClockInterface::class);

                assert($rememberMeRepo instanceof RememberMeRepositoryInterface);
                assert($sessionRepo instanceof AdminSessionRepositoryInterface);
                assert($pdo instanceof PDO);
                assert($clock instanceof \Maatify\SharedCommon\Contracts\ClockInterface);

                return new RememberMeService(
                    $rememberMeRepo,
                    $sessionRepo,
                    $pdo,
                    $clock
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

                assert($contextFactory instanceof CryptoContextFactory);
                assert($directFactory instanceof CryptoDirectFactory);

                return new CryptoProvider($contextFactory, $directFactory);
            },
            EmailQueueWriterInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                $crypto = $c->get(CryptoProvider::class);
                $cryptoContextProvider = $c->get(CryptoContextProviderInterface::class);

                assert($pdo instanceof PDO);
                assert($crypto instanceof CryptoProvider);
                assert($cryptoContextProvider instanceof CryptoContextProviderInterface);

                return new PdoEmailQueueWriter($pdo, $crypto, $cryptoContextProvider);
            },
            EmailRendererInterface::class => function (ContainerInterface $c) use ($templatesPath) {
                $templatesPath = $templatesPath ?? (__DIR__ . '/../Templates');
                return new TwigEmailRenderer($templatesPath);
            },
            EmailTransportInterface::class => function (ContainerInterface $c) {
                $config = $c->get(EmailTransportConfigDTO::class);
                assert($config instanceof EmailTransportConfigDTO);
                return new SmtpEmailTransport($config);
            },

            NotificationCryptoServiceInterface::class => function (ContainerInterface $c) {
                $cryptoProvider = $c->get(CryptoProvider::class);
                $cryptoContextProvider = $c->get(CryptoContextProviderInterface::class);

                assert($cryptoProvider instanceof CryptoProvider);
                assert($cryptoContextProvider instanceof CryptoContextProviderInterface);

                return new NotificationCryptoService($cryptoProvider, $cryptoContextProvider);
            },

            TotpSecretCryptoServiceInterface::class => function (ContainerInterface $c) {
                $cryptoProvider = $c->get(CryptoProvider::class);
                $cryptoContextProvider = $c->get(CryptoContextProviderInterface::class);

                assert($cryptoProvider instanceof CryptoProvider);
                assert($cryptoContextProvider instanceof CryptoContextProviderInterface);

                return new TotpSecretCryptoService($cryptoProvider, $cryptoContextProvider);
            },


            AdminIdentifierCryptoServiceInterface::class => function (ContainerInterface $c) {
                $cryptoProvider = $c->get(CryptoProvider::class);
                $adminRuntimeConfigDTO = $c->get(AdminRuntimeConfigDTO::class);
                $cryptoContextProvider = $c->get(CryptoContextProviderInterface::class);

                assert($cryptoProvider instanceof CryptoProvider);
                assert($adminRuntimeConfigDTO instanceof AdminRuntimeConfigDTO);
                assert($cryptoContextProvider instanceof CryptoContextProviderInterface);

                $blindIndexPepper = $adminRuntimeConfigDTO->emailBlindIndexKey;

                return new AdminIdentifierCryptoService(
                    $cryptoProvider,
                    $blindIndexPepper,
                    $cryptoContextProvider
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
            \Maatify\AuditTrail\Recorder\AuditTrailRecorder::class => function (ContainerInterface $c) {
                $logger = $c->get(\Maatify\AuditTrail\Contract\AuditTrailLoggerInterface::class);
                $clock = $c->get(ClockInterface::class);
                $fallbackLogger = $c->get(LoggerInterface::class);

                assert($logger instanceof \Maatify\AuditTrail\Contract\AuditTrailLoggerInterface);
                assert($clock instanceof ClockInterface);

                return new \Maatify\AuditTrail\Recorder\AuditTrailRecorder($logger, $clock, $fallbackLogger instanceof LoggerInterface ? $fallbackLogger : null);
            },
            \Maatify\AdminKernel\Application\Contracts\AuditTrailRecorderInterface::class => function (ContainerInterface $c) {
                $recorder = $c->get(\Maatify\AuditTrail\Recorder\AuditTrailRecorder::class);
                assert($recorder instanceof \Maatify\AuditTrail\Recorder\AuditTrailRecorder);
                return new \Maatify\AdminKernel\Infrastructure\Logging\AuditTrailMaatifyAdapter($recorder);
            },

            // 2. Authoritative Audit
            \Maatify\AuthoritativeAudit\Contract\AuthoritativeAuditOutboxWriterInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new \Maatify\AuthoritativeAudit\Infrastructure\Mysql\AuthoritativeAuditOutboxWriterMysqlRepository($pdo);
            },
            \Maatify\AuthoritativeAudit\Recorder\AuthoritativeAuditRecorder::class => function (ContainerInterface $c) {
                $writer = $c->get(\Maatify\AuthoritativeAudit\Contract\AuthoritativeAuditOutboxWriterInterface::class);
                $clock = $c->get(ClockInterface::class);
                // AuthoritativeAuditRecorder does NOT accept a fallback logger in constructor

                assert($writer instanceof \Maatify\AuthoritativeAudit\Contract\AuthoritativeAuditOutboxWriterInterface);
                assert($clock instanceof ClockInterface);

                return new \Maatify\AuthoritativeAudit\Recorder\AuthoritativeAuditRecorder($writer, $clock);
            },
            \Maatify\AdminKernel\Application\Contracts\AuthoritativeAuditRecorderInterface::class => function (ContainerInterface $c) {
                $recorder = $c->get(\Maatify\AuthoritativeAudit\Recorder\AuthoritativeAuditRecorder::class);
                $requestContext = $c->get(\Maatify\AdminKernel\Context\RequestContext::class);

                assert($recorder instanceof \Maatify\AuthoritativeAudit\Recorder\AuthoritativeAuditRecorder);
                assert($requestContext instanceof \Maatify\AdminKernel\Context\RequestContext);

                return new \Maatify\AdminKernel\Infrastructure\Logging\AuthoritativeAuditMaatifyAdapter($recorder, $requestContext);
            },

            // 3. Behavior Trace (Operational Activity)
            \Maatify\BehaviorTrace\Contract\BehaviorTraceWriterInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new \Maatify\BehaviorTrace\Infrastructure\Mysql\BehaviorTraceWriterMysqlRepository($pdo);
            },
            \Maatify\BehaviorTrace\Recorder\BehaviorTraceRecorder::class => function (ContainerInterface $c) {
                $writer = $c->get(\Maatify\BehaviorTrace\Contract\BehaviorTraceWriterInterface::class);
                $clock = $c->get(ClockInterface::class);
                $fallbackLogger = $c->get(LoggerInterface::class);

                assert($writer instanceof \Maatify\BehaviorTrace\Contract\BehaviorTraceWriterInterface);
                assert($clock instanceof ClockInterface);

                return new \Maatify\BehaviorTrace\Recorder\BehaviorTraceRecorder($writer, $clock, $fallbackLogger instanceof LoggerInterface ? $fallbackLogger : null);
            },
            \Maatify\AdminKernel\Application\Contracts\BehaviorTraceRecorderInterface::class => function (ContainerInterface $c) {
                $recorder = $c->get(\Maatify\BehaviorTrace\Recorder\BehaviorTraceRecorder::class);
                assert($recorder instanceof \Maatify\BehaviorTrace\Recorder\BehaviorTraceRecorder);
                return new \Maatify\AdminKernel\Infrastructure\Logging\BehaviorTraceMaatifyAdapter($recorder);
            },

            // 4. Delivery Operations
            \Maatify\DeliveryOperations\Contract\DeliveryOperationsLoggerInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new \Maatify\DeliveryOperations\Infrastructure\Mysql\DeliveryOperationsLoggerMysqlRepository($pdo);
            },
            \Maatify\DeliveryOperations\Recorder\DeliveryOperationsRecorder::class => function (ContainerInterface $c) {
                $logger = $c->get(\Maatify\DeliveryOperations\Contract\DeliveryOperationsLoggerInterface::class);
                $clock = $c->get(ClockInterface::class);
                $fallbackLogger = $c->get(LoggerInterface::class);

                assert($logger instanceof \Maatify\DeliveryOperations\Contract\DeliveryOperationsLoggerInterface);
                assert($clock instanceof ClockInterface);

                return new \Maatify\DeliveryOperations\Recorder\DeliveryOperationsRecorder($logger, $clock, $fallbackLogger instanceof LoggerInterface ? $fallbackLogger : null);
            },
            \Maatify\AdminKernel\Application\Contracts\DeliveryOperationsRecorderInterface::class => function (ContainerInterface $c) {
                $recorder = $c->get(\Maatify\DeliveryOperations\Recorder\DeliveryOperationsRecorder::class);
                assert($recorder instanceof \Maatify\DeliveryOperations\Recorder\DeliveryOperationsRecorder);
                return new \Maatify\AdminKernel\Infrastructure\Logging\DeliveryOperationsMaatifyAdapter($recorder);
            },

            // 5. Diagnostics Telemetry
            \Maatify\DiagnosticsTelemetry\Contract\DiagnosticsTelemetryLoggerInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new \Maatify\DiagnosticsTelemetry\Infrastructure\Mysql\DiagnosticsTelemetryLoggerMysqlRepository($pdo);
            },
            \Maatify\DiagnosticsTelemetry\Recorder\DiagnosticsTelemetryRecorder::class => function (ContainerInterface $c) {
                $logger = $c->get(\Maatify\DiagnosticsTelemetry\Contract\DiagnosticsTelemetryLoggerInterface::class);
                $clock = $c->get(ClockInterface::class);
                $fallbackLogger = $c->get(LoggerInterface::class);

                assert($logger instanceof \Maatify\DiagnosticsTelemetry\Contract\DiagnosticsTelemetryLoggerInterface);
                assert($clock instanceof ClockInterface);

                return new \Maatify\DiagnosticsTelemetry\Recorder\DiagnosticsTelemetryRecorder($logger, $clock, $fallbackLogger instanceof LoggerInterface ? $fallbackLogger : null);
            },
            \Maatify\AdminKernel\Application\Contracts\DiagnosticsTelemetryRecorderInterface::class => function (ContainerInterface $c) {
                $recorder = $c->get(\Maatify\DiagnosticsTelemetry\Recorder\DiagnosticsTelemetryRecorder::class);
                assert($recorder instanceof \Maatify\DiagnosticsTelemetry\Recorder\DiagnosticsTelemetryRecorder);
                return new \Maatify\AdminKernel\Infrastructure\Logging\DiagnosticsTelemetryMaatifyAdapter($recorder);
            },

            // 6. Security Signals
            \Maatify\SecuritySignals\Contract\SecuritySignalsLoggerInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new \Maatify\SecuritySignals\Infrastructure\Mysql\SecuritySignalsLoggerMysqlRepository($pdo);
            },
            \Maatify\SecuritySignals\Recorder\SecuritySignalsRecorder::class => function (ContainerInterface $c) {
                $logger = $c->get(\Maatify\SecuritySignals\Contract\SecuritySignalsLoggerInterface::class);
                $clock = $c->get(ClockInterface::class);
                $fallbackLogger = $c->get(LoggerInterface::class);

                assert($logger instanceof \Maatify\SecuritySignals\Contract\SecuritySignalsLoggerInterface);
                assert($clock instanceof ClockInterface);

                return new \Maatify\SecuritySignals\Recorder\SecuritySignalsRecorder($logger, $clock, $fallbackLogger instanceof LoggerInterface ? $fallbackLogger : null);
            },
            \Maatify\AdminKernel\Application\Contracts\SecuritySignalsRecorderInterface::class => function (ContainerInterface $c) {
                $recorder = $c->get(\Maatify\SecuritySignals\Recorder\SecuritySignalsRecorder::class);
                assert($recorder instanceof \Maatify\SecuritySignals\Recorder\SecuritySignalsRecorder);
                return new \Maatify\AdminKernel\Infrastructure\Logging\SecuritySignalsMaatifyAdapter($recorder);
            },

            PermissionMapperInterface::class => function () {
                return new PermissionMapper();
            },

            \Maatify\AdminKernel\Domain\Contracts\Roles\RolePermissionsRepositoryInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new PdoRolePermissionsRepository($pdo);
            },

            \Maatify\AdminKernel\Http\Controllers\Ui\UiRoleDetailsController::class => function (ContainerInterface $c) {
                $view = $c->get(Twig::class);
                $authorizationService = $c->get(AuthorizationService::class);
                $roleRepository = $c->get(\Maatify\AdminKernel\Domain\Contracts\Roles\RoleRepositoryInterface::class);

                assert($view instanceof Twig);
                assert($authorizationService instanceof AuthorizationService);
                assert($roleRepository instanceof \Maatify\AdminKernel\Domain\Contracts\Roles\RoleRepositoryInterface);

                return new \Maatify\AdminKernel\Http\Controllers\Ui\UiRoleDetailsController($view, $authorizationService, $roleRepository);
            },

            \Maatify\AdminKernel\Http\Controllers\Api\Roles\RolePermissionAssignController::class => function (ContainerInterface $c) {
                $validationGuard = $c->get(ValidationGuard::class);
                $updater = $c->get(RolePermissionsRepositoryInterface::class);

                assert($validationGuard instanceof ValidationGuard);
                assert($updater instanceof RolePermissionsRepositoryInterface);

                return new \Maatify\AdminKernel\Http\Controllers\Api\Roles\RolePermissionAssignController($validationGuard, $updater);
            },

            \Maatify\AdminKernel\Http\Controllers\Api\Roles\RolePermissionUnassignController::class => function (ContainerInterface $c) {
                $validationGuard = $c->get(ValidationGuard::class);
                $updater = $c->get(RolePermissionsRepositoryInterface::class);
                assert($validationGuard instanceof ValidationGuard);
                assert($updater instanceof RolePermissionsRepositoryInterface);
                return new \Maatify\AdminKernel\Http\Controllers\Api\Roles\RolePermissionUnassignController($validationGuard, $updater);
            },

            \Maatify\AdminKernel\Http\Controllers\Api\Roles\RolePermissionsQueryController::class => function (ContainerInterface $c) {
                $reader = $c->get(\Maatify\AdminKernel\Domain\Contracts\Roles\RolePermissionsRepositoryInterface::class);
                $validationGuard = $c->get(ValidationGuard::class);
                $filterResolver = $c->get(ListFilterResolver::class);
                assert($reader instanceof \Maatify\AdminKernel\Domain\Contracts\Roles\RolePermissionsRepositoryInterface);
                assert($validationGuard instanceof ValidationGuard);
                assert($filterResolver instanceof ListFilterResolver);
                return new \Maatify\AdminKernel\Http\Controllers\Api\Roles\RolePermissionsQueryController($reader, $validationGuard, $filterResolver);
            },

            \Maatify\AdminKernel\Domain\Contracts\Roles\RoleAdminsRepositoryInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new PdoRoleAdminsRepository($pdo);
            },

            \Maatify\AdminKernel\Http\Controllers\Api\Roles\RoleAdminAssignController::class => function (ContainerInterface $c) {
                $validationGuard = $c->get(ValidationGuard::class);
                $updater = $c->get(RoleAdminsRepositoryInterface::class);
                assert($validationGuard instanceof ValidationGuard);
                assert($updater instanceof RoleAdminsRepositoryInterface);

                return new \Maatify\AdminKernel\Http\Controllers\Api\Roles\RoleAdminAssignController($validationGuard, $updater);
            },

            \Maatify\AdminKernel\Http\Controllers\Api\Roles\RoleAdminUnassignController::class => function (ContainerInterface $c) {
                $validationGuard = $c->get(ValidationGuard::class);
                $updater = $c->get(RoleAdminsRepositoryInterface::class);
                assert($validationGuard instanceof ValidationGuard);
                assert($updater instanceof RoleAdminsRepositoryInterface);

                return new \Maatify\AdminKernel\Http\Controllers\Api\Roles\RoleAdminUnassignController($validationGuard, $updater);
            },

            \Maatify\AdminKernel\Http\Controllers\Api\Roles\RoleAdminsQueryController::class => function (ContainerInterface $c) {
                $reader = $c->get(\Maatify\AdminKernel\Domain\Contracts\Roles\RoleAdminsRepositoryInterface::class);
                $validationGuard = $c->get(ValidationGuard::class);
                $filterResolver = $c->get(ListFilterResolver::class);
                assert($reader instanceof \Maatify\AdminKernel\Domain\Contracts\Roles\RoleAdminsRepositoryInterface);
                assert($validationGuard instanceof ValidationGuard);
                assert($filterResolver instanceof ListFilterResolver);
                return new \Maatify\AdminKernel\Http\Controllers\Api\Roles\RoleAdminsQueryController($reader, $validationGuard, $filterResolver);
            },

            \Maatify\AdminKernel\Domain\Contracts\Roles\AdminRolesRepositoryInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new \Maatify\AdminKernel\Infrastructure\Repository\Roles\PdoAdminRolesRepository($pdo);
            },

            \Maatify\AdminKernel\Http\Controllers\Api\Admin\AdminRolesQueryController::class => function (ContainerInterface $c) {
                $reader = $c->get(\Maatify\AdminKernel\Domain\Contracts\Roles\AdminRolesRepositoryInterface::class);
                $validationGuard = $c->get(ValidationGuard::class);
                $filterResolver = $c->get(ListFilterResolver::class);
                assert($reader instanceof \Maatify\AdminKernel\Domain\Contracts\Roles\AdminRolesRepositoryInterface);
                assert($validationGuard instanceof ValidationGuard);
                assert($filterResolver instanceof ListFilterResolver);
                return new \Maatify\AdminKernel\Http\Controllers\Api\Admin\AdminRolesQueryController($reader, $validationGuard, $filterResolver);
            },


            \Maatify\AdminKernel\Domain\Contracts\Permissions\EffectivePermissionsRepositoryInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new \Maatify\AdminKernel\Infrastructure\Repository\Permissions\PdoEffectivePermissionsRepository($pdo);
            },

            \Maatify\AdminKernel\Http\Controllers\Api\Admin\EffectivePermissionsQueryController::class => function (ContainerInterface $c) {
                $reader = $c->get(\Maatify\AdminKernel\Domain\Contracts\Permissions\EffectivePermissionsRepositoryInterface::class);
                $validationGuard = $c->get(ValidationGuard::class);
                $filterResolver = $c->get(ListFilterResolver::class);
                assert($reader instanceof \Maatify\AdminKernel\Domain\Contracts\Permissions\EffectivePermissionsRepositoryInterface);
                assert($validationGuard instanceof ValidationGuard);
                assert($filterResolver instanceof ListFilterResolver);
                return new \Maatify\AdminKernel\Http\Controllers\Api\Admin\EffectivePermissionsQueryController($reader, $validationGuard, $filterResolver);
            },

            \Maatify\AdminKernel\Domain\Contracts\Permissions\DirectPermissionsRepositoryInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new \Maatify\AdminKernel\Infrastructure\Repository\Permissions\PdoDirectPermissionsRepository($pdo);
            },

            \Maatify\AdminKernel\Http\Controllers\Api\Admin\DirectPermissionsQueryController::class => function (ContainerInterface $c) {
                $reader = $c->get(\Maatify\AdminKernel\Domain\Contracts\Permissions\DirectPermissionsRepositoryInterface::class);
                $validationGuard = $c->get(ValidationGuard::class);
                $filterResolver = $c->get(ListFilterResolver::class);
                assert($reader instanceof \Maatify\AdminKernel\Domain\Contracts\Permissions\DirectPermissionsRepositoryInterface);
                assert($validationGuard instanceof ValidationGuard);
                assert($filterResolver instanceof ListFilterResolver);
                return new \Maatify\AdminKernel\Http\Controllers\Api\Admin\DirectPermissionsQueryController($reader, $validationGuard, $filterResolver);
            },

            \Maatify\AdminKernel\Domain\Contracts\Permissions\DirectPermissionsWriterRepositoryInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new \Maatify\AdminKernel\Infrastructure\Repository\Permissions\PdoDirectPermissionsWriterRepository($pdo);
            },

            \Maatify\AdminKernel\Http\Controllers\Api\Admin\AssignDirectPermissionController::class => function (ContainerInterface $c) {
                $validationGuard = $c->get(ValidationGuard::class);
                $updater = $c->get(DirectPermissionsWriterRepositoryInterface::class);
                assert($validationGuard instanceof ValidationGuard);
                assert($updater instanceof DirectPermissionsWriterRepositoryInterface);
                return new \Maatify\AdminKernel\Http\Controllers\Api\Admin\AssignDirectPermissionController($validationGuard, $updater);
            },

            \Maatify\AdminKernel\Http\Controllers\Api\Admin\RevokeDirectPermissionController::class => function (ContainerInterface $c) {
                $validationGuard = $c->get(ValidationGuard::class);
                $updater = $c->get(DirectPermissionsWriterRepositoryInterface::class);
                assert($validationGuard instanceof ValidationGuard);
                assert($updater instanceof DirectPermissionsWriterRepositoryInterface);
                return new \Maatify\AdminKernel\Http\Controllers\Api\Admin\RevokeDirectPermissionController($validationGuard, $updater);
            },

            \Maatify\AdminKernel\Domain\Contracts\Permissions\DirectPermissionsAssignableRepositoryInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new \Maatify\AdminKernel\Infrastructure\Repository\Permissions\PdoDirectPermissionsAssignableRepository($pdo);
            },

            \Maatify\AdminKernel\Http\Controllers\Api\Admin\DirectPermissionsAssignableQueryController::class => function (ContainerInterface $c) {
                $repo = $c->get(\Maatify\AdminKernel\Domain\Contracts\Permissions\DirectPermissionsAssignableRepositoryInterface::class);
                $validationGuard = $c->get(ValidationGuard::class);
                $filterResolver = $c->get(ListFilterResolver::class);
                assert($repo instanceof \Maatify\AdminKernel\Domain\Contracts\Permissions\DirectPermissionsAssignableRepositoryInterface);
                assert($validationGuard instanceof ValidationGuard);
                assert($filterResolver instanceof ListFilterResolver);
                return new \Maatify\AdminKernel\Http\Controllers\Api\Admin\DirectPermissionsAssignableQueryController($repo, $validationGuard, $filterResolver);
            },

            \Maatify\AdminKernel\Domain\Contracts\Permissions\PermissionDetailsRepositoryInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new \Maatify\AdminKernel\Infrastructure\Repository\Permissions\PdoPermissionDetailsRepository($pdo);
            },

            \Maatify\AdminKernel\Http\Controllers\Ui\UiAPermissionDetailsController::class => function (ContainerInterface $c) {
                $view = $c->get(Twig::class);
                $authorizationService = $c->get(AuthorizationService::class);
                $permissionDetailsRepository = $c->get(\Maatify\AdminKernel\Domain\Contracts\Permissions\PermissionDetailsRepositoryInterface::class);
                assert($view instanceof Twig);
                assert($authorizationService instanceof AuthorizationService);
                assert($permissionDetailsRepository instanceof \Maatify\AdminKernel\Domain\Contracts\Permissions\PermissionDetailsRepositoryInterface);
                return new \Maatify\AdminKernel\Http\Controllers\Ui\UiAPermissionDetailsController($view, $authorizationService, $permissionDetailsRepository);
            },

            \Maatify\AdminKernel\Domain\Contracts\Permissions\PermissionRolesQueryRepositoryInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new \Maatify\AdminKernel\Infrastructure\Repository\Permissions\PdoPermissionRolesQueryRepository($pdo);
            },

            \Maatify\AdminKernel\Http\Controllers\Api\Permissions\PermissionRolesQueryController::class => function (ContainerInterface $c) {
                $reader = $c->get(\Maatify\AdminKernel\Domain\Contracts\Permissions\PermissionRolesQueryRepositoryInterface::class);
                $validationGuard = $c->get(ValidationGuard::class);
                $filterResolver = $c->get(ListFilterResolver::class);
                assert($reader instanceof \Maatify\AdminKernel\Domain\Contracts\Permissions\PermissionRolesQueryRepositoryInterface);
                assert($validationGuard instanceof ValidationGuard);
                assert($filterResolver instanceof ListFilterResolver);
                return new \Maatify\AdminKernel\Http\Controllers\Api\Permissions\PermissionRolesQueryController($reader, $validationGuard, $filterResolver);
            },

            \Maatify\AdminKernel\Domain\Contracts\Permissions\PermissionAdminsQueryRepositoryInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);
                return new \Maatify\AdminKernel\Infrastructure\Repository\Permissions\PdoPermissionAdminsQueryRepository($pdo);
            },

            \Maatify\AbuseProtection\Contracts\AbuseSignatureProviderInterface::class => function (ContainerInterface $c) {
                $keyRotation = $c->get(KeyRotationService::class);
                $hkdf = $c->get(HKDFService::class);
                $cryptoContextProvider = $c->get(CryptoContextProviderInterface::class);

                assert($keyRotation instanceof KeyRotationService);
                assert($hkdf instanceof HKDFService);
                assert($cryptoContextProvider instanceof CryptoContextProviderInterface);

                return new \Maatify\AdminKernel\Infrastructure\Crypto\AbuseProtectionCryptoSignatureProvider($keyRotation, $hkdf, $cryptoContextProvider);
            },

            \Maatify\AdminKernel\Domain\Contracts\Abuse\AbuseCookieServiceInterface::class => function (ContainerInterface $c) {
                $signatureProvider = $c->get(AbuseSignatureProviderInterface::class);
                assert($signatureProvider instanceof AbuseSignatureProviderInterface);
                return new AbuseCookieService($signatureProvider);
            },

            \Maatify\AbuseProtection\Contracts\AbuseDecisionInterface::class => function (ContainerInterface $c) {
                return new LoginAbusePolicy(
//                    challengeAfterFailures: 0,
                );
            },

            \Maatify\AbuseProtection\Contracts\ChallengeProviderInterface::class
            => function (ContainerInterface $c) use ($abuseProvider) {

                /*
                 * IMPORTANT:
                 * Exactly ONE challenge provider is allowed at runtime.
                 * Provider selection is controlled exclusively by ABUSE_CHALLENGE_PROVIDER.
                 *
                 * Any missing or invalid configuration MUST fail fast.
                 */

                return match ($abuseProvider) {

                    AbuseChallengeProviderEnum::NONE =>
                    new NullChallengeProvider(),

                    AbuseChallengeProviderEnum::TURNSTILE => (function () use ($c) {
                        $config = $c->get(TurnstileConfigDTO::class);
                        assert($config instanceof TurnstileConfigDTO);

                        $secret = (string) ($config->secretKey ?? '');
                        if ($secret === '') {
                            throw new \RuntimeException('TURNSTILE_SECRET_KEY is missing.');
                        }

                        return new TurnstileChallengeProvider($secret);
                    })(),

                    AbuseChallengeProviderEnum::HCAPTCHA => (function () use ($c) {
                        $config = $c->get(HCaptchaConfigDTO::class);
                        assert($config instanceof HCaptchaConfigDTO);

                        $secret = (string) ($config->secretKey ?? '');
                        if ($secret === '') {
                            throw new \RuntimeException('HCAPTCHA_SECRET_KEY is missing.');
                        }

                        return new HCaptchaChallengeProvider($secret);
                    })(),

                    AbuseChallengeProviderEnum::RECAPTCHA_V2 => (function () use ($c) {
                        $config = $c->get(RecaptchaV2ConfigDTO::class);
                        assert($config instanceof RecaptchaV2ConfigDTO);

                        $secret = (string) ($config->secretKey ?? '');
                        if ($secret === '') {
                            throw new \RuntimeException('RECAPTCHA_V2_SECRET_KEY is missing.');
                        }

                        return new RecaptchaV2ChallengeProvider($secret);
                    })(),
                };
            },

            \Maatify\AdminKernel\Domain\Contracts\Abuse\ChallengeWidgetRendererInterface::class
            => function (ContainerInterface $c) use ($abuseProvider) {

                /*
                 * Widget renderer MUST match the active challenge provider.
                 * No fallback, no multiple renderers, no implicit defaults.
                 */

                return match ($abuseProvider) {

                    AbuseChallengeProviderEnum::NONE =>
                    new NullChallengeWidgetRenderer(),

                    AbuseChallengeProviderEnum::TURNSTILE => (function () use ($c) {
                        $config = $c->get(TurnstileConfigDTO::class);
                        assert($config instanceof TurnstileConfigDTO);

                        return new TurnstileWidgetRenderer(
                            (string) ($config->siteKey ?? '')
                        );
                    })(),

                    AbuseChallengeProviderEnum::HCAPTCHA => (function () use ($c) {
                        $config = $c->get(HCaptchaConfigDTO::class);
                        assert($config instanceof HCaptchaConfigDTO);

                        return new HCaptchaWidgetRenderer(
                            (string) ($config->siteKey ?? '')
                        );
                    })(),

                    AbuseChallengeProviderEnum::RECAPTCHA_V2 => (function () use ($c) {
                        $config = $c->get(RecaptchaV2ConfigDTO::class);
                        assert($config instanceof RecaptchaV2ConfigDTO);

                        return new RecaptchaV2WidgetRenderer(
                            (string) ($config->siteKey ?? '')
                        );
                    })(),
                };
            },

            LanguageQueryReaderInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                assert($pdo instanceof PDO);

                return new PdoLanguageQueryReader($pdo);
            },

            AdminQueryController::class => function (ContainerInterface $c) {
                $reader = $c->get(AdminQueryReaderInterface::class);
                $validationGuard = $c->get(ValidationGuard::class);
                $filterResolver = $c->get(ListFilterResolver::class);
                assert($reader instanceof AdminQueryReaderInterface);
                assert($validationGuard instanceof ValidationGuard);
                assert($filterResolver instanceof ListFilterResolver);
                return new AdminQueryController($reader, $validationGuard, $filterResolver);
            }

        ]);

        // Extension Hook: Allow host projects to override/extend bindings
        if ($builderHook !== null) {
            $builderHook($containerBuilder);
        }

        return $containerBuilder->build();
    }
}
