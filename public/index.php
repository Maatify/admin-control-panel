<?php

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

require __DIR__ . '/../vendor/autoload.php';

use DI\ContainerBuilder;
use Maatify\AdminKernel\Bootstrap\AdminKernelPermissionBindings;
use Maatify\AdminKernel\Kernel\AdminKernel;
use Maatify\AdminKernel\Kernel\KernelOptions;
use Maatify\AdminKernel\Kernel\DTO\AdminRuntimeConfigDTO;
use Dotenv\Dotenv;
use Maatify\SettingsSlim\Admin\Security\SettingAdminPermissionPackage;
use Maatify\AdminKernel\Ui\Config\MediaUrlConfigDTO;
use Maatify\CurrencySlim\Admin\Security\CurrencyAdminPermissionPackage;
use Maatify\ExchangeRatesSlim\Admin\Security\ExchangeRatesAdminPermissionPackage;
use Maatify\GeoSlim\Admin\Security\GeoAdminPermissionPackage;
use Maatify\Storage\Bootstrap\StorageBindings;
use Maatify\Storage\Config\StorageConfig;

//use Maatify\PsrLogger\LoggerFactory;

// 1️⃣ Load ENV (HOST responsibility)
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

//$logger = LoggerFactory::create('app/errors');
//$logger->alert('access');

// 2️⃣ Build Runtime Config DTO
$runtimeConfig = AdminRuntimeConfigDTO::fromArray($_ENV);
$storageConfig = StorageConfig::fromEnv($_ENV);
$mediaUrlConfig = MediaUrlConfigDTO::fromArray($_ENV);

// 3️⃣ Kernel options
$options = new KernelOptions();
$options->runtimeConfig = $runtimeConfig;

// (اختياري)
// $options->registerInfrastructureMiddleware = true;
// $options->strictInfrastructure = true;
// $options->routes = fn ($app) => ...;

$permissionPackages = [
    new SettingAdminPermissionPackage(),
    new CurrencyAdminPermissionPackage(),
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

// 5️⃣ Boot & Run
AdminKernel::bootWithOptions($options)->run();
