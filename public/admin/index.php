<?php

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__, 2));

require __DIR__ . '/../../vendor/autoload.php';

use DI\ContainerBuilder;
use Dotenv\Dotenv;
use Maatify\AdminKernel\Bootstrap\AdminEnvironmentAdapter;
use Maatify\AdminKernel\Bootstrap\AdminStorageEnvAdapter;
use Maatify\AdminKernel\Bootstrap\AdminKernelPermissionBindings;
use Maatify\AdminKernel\Kernel\AdminKernel;
use Maatify\AdminKernel\Kernel\KernelOptions;
use Maatify\AdminKernel\Kernel\DTO\AdminRuntimeConfigDTO;
use Maatify\SettingsSlim\Admin\Security\SettingAdminPermissionPackage;
use Maatify\AdminKernel\Ui\Config\MediaUrlConfigDTO;
use Maatify\CurrencySlim\Admin\Security\CurrencyAdminPermissionPackage;
use Maatify\ExchangeRatesSlim\Admin\Security\ExchangeRatesAdminPermissionPackage;
use Maatify\GeoSlim\Admin\Security\GeoAdminPermissionPackage;
use Maatify\Storage\Bootstrap\StorageBindings;
use Maatify\Storage\Config\StorageConfig;

//use Maatify\PsrLogger\LoggerFactory;

// 1️⃣ Load ENV (HOST responsibility)
$dotenv = Dotenv::createImmutable(APP_ROOT);
$dotenv->safeLoad();

//$logger = LoggerFactory::create('app/errors');
//$logger->alert('access');

// LOCAL_BASE_PATH is the generic local-storage contract. It is kept separate
// from the admin-specific storage contract below.
$localBasePath = $_ENV['LOCAL_BASE_PATH'] ?? '';
if ($localBasePath !== '' && !str_starts_with($localBasePath, '/')) {
    $localBasePath = rtrim(APP_ROOT, '/') . '/' . ltrim($localBasePath, '/');
    $_ENV['LOCAL_BASE_PATH'] = $localBasePath;
    putenv('LOCAL_BASE_PATH=' . $localBasePath);
}

// The admin document root is public/admin, so an empty admin local-storage
// value resolves to that host's documented default.
$adminLocalBasePath = $_ENV['ADMIN_LOCAL_BASE_PATH'] ?? '';
if ($adminLocalBasePath === '') {
    $adminLocalBasePath = 'public/admin/images';
}
if (!str_starts_with($adminLocalBasePath, '/')) {
    $adminLocalBasePath = rtrim(APP_ROOT, '/') . '/' . ltrim($adminLocalBasePath, '/');
}
$_ENV['ADMIN_LOCAL_BASE_PATH'] = $adminLocalBasePath;
putenv('ADMIN_LOCAL_BASE_PATH=' . $adminLocalBasePath);

$adminEnv = $_ENV;

// 2️⃣ Build Runtime Config DTO
$kernelEnv = AdminEnvironmentAdapter::forAdminKernel($adminEnv);
$runtimeConfig = AdminRuntimeConfigDTO::fromArray($kernelEnv);
$storageConfig = StorageConfig::fromEnv(AdminStorageEnvAdapter::adapt($adminEnv));
$mediaUrlConfig = MediaUrlConfigDTO::fromArray($kernelEnv);

// 3️⃣ Kernel options
$options = new KernelOptions();
$options->runtimeConfig = $runtimeConfig;

// (اختياري)
// $options->registerInfrastructureMiddleware = true;
// $options->strictInfrastructure = true;
// $options->routes = fn ($app) => ...;

$permissionPackages = [
    new CurrencyAdminPermissionPackage(),
    new SettingAdminPermissionPackage(),
    new ExchangeRatesAdminPermissionPackage(),
    new GeoAdminPermissionPackage(),
    // new PaymentMethodPackage(),
    // new ExchangeRatesPackage(),
    // new ShippingPackage(),
];

// 4️⃣  Register host-specific bindings
$options->builderHook = static function (ContainerBuilder $containerBuilder) use (
    $storageConfig,
    $mediaUrlConfig,
    $permissionPackages
): void {
    StorageBindings::register(
        $containerBuilder,
        APP_ROOT,
        $storageConfig,
    );

    $containerBuilder->addDefinitions([
        MediaUrlConfigDTO::class => static fn (): MediaUrlConfigDTO => $mediaUrlConfig,
    ]);

    AdminKernelPermissionBindings::register(
        $containerBuilder,
        $permissionPackages,
    );

    // ------- Register Infrastructure modules -------

    // Register ContentDocuments Infrastructure
    \Maatify\AdminKernel\Infrastructure\ContentDocuments\Bootstrap\ContentDocumentBinding::register($containerBuilder);

    // ------- Register internal modules -------

    // Register Maatify\LanguageCore modules
    \Maatify\LanguageCore\Bootstrap\LanguageCoreBindings::register($containerBuilder);

    // Register Maatify\I18n modules
    \Maatify\I18n\Bootstrap\I18nBindings::register($containerBuilder);

    // Register Maatify\ContentDocuments modules
    \Maatify\ContentDocuments\Bootstrap\ContentDocumentsBindings::register($containerBuilder);

    // Register Maatify\Validation modules
    \Maatify\Validation\Bootstrap\ValidationBindings::register($containerBuilder);

    // Register Maatify\AppSettings modules
    \Maatify\AppSettings\Bootstrap\AppSettingsBindings::register($containerBuilder);

    // Register Maatify\SettingsSlim modules
    \Maatify\Settings\Bootstrap\SettingsBindings::register($containerBuilder);

    // Register Maatify\Verification modules
    \Maatify\Verification\Bootstrap\VerificationBindings::register($containerBuilder);

    // Register Maatify\LanguageCoreBinding modules
    \Maatify\AdminKernel\Infrastructure\LanguageCore\Bootstrap\LanguageCoreBinding::register($containerBuilder);

    // Register Maatify\ImageProfileBindings modules
    \Maatify\ImageProfile\Bootstrap\ImageProfileBindings::register($containerBuilder);

    // Register Maatify\WebsiteUiThemeBindings modules
    \Maatify\WebsiteUiTheme\Bootstrap\WebsiteUiThemeBindings::register($containerBuilder);

    // Register Maatify\CurrenciesBindings modules
    \Maatify\Currency\Bootstrap\CurrenciesBindings::register($containerBuilder);

    // Register Maatify\ExchangeRatesBindings modules
    \Maatify\ExchangeRates\Bootstrap\ExchangeRatesBindings::register($containerBuilder);

    // Register Maatify\Geo module
    \Maatify\Geo\Bootstrap\GeoBindings::register($containerBuilder);
};

// 5️⃣ Twig compiled-template cache (production only)
if (($runtimeConfig->appEnv ?? 'local') === 'production') {
    $options->twigCachePath = APP_ROOT . '/storage/twig/admin';
}

// 6️⃣ Boot & Run
AdminKernel::bootWithOptions($options)->run();
