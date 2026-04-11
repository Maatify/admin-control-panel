<?php

declare(strict_types=1);

// ① من $_ENV
use DI\ContainerBuilder;
use Maatify\Storage\Bootstrap\StorageBindings;
use Maatify\Storage\Config\DOSpacesConfig;
use Maatify\Storage\Config\StorageConfig;

define('APP_ROOT', dirname(__DIR__));

require APP_ROOT . '/vendor/autoload.php';

$containerBuilder = new ContainerBuilder();

StorageBindings::register(
    $containerBuilder,
    APP_ROOT,
    StorageConfig::fromEnv($_ENV),
);

// ② يدوي — local
StorageBindings::register(
    $containerBuilder,
    APP_ROOT,
    new StorageConfig(driver: 'local'),
);

// ③ يدوي — DO Spaces
StorageBindings::register(
    $containerBuilder,
    APP_ROOT,
    new StorageConfig(
        driver: 'do_spaces',
        doSpaces: new DOSpacesConfig(
            key:      'xxx',
            secret:   'xxx',
            endpoint: 'https://fra1.digitaloceanspaces.com',
            bucket:   'my-bucket',
            region:   'fra1',
            cdnUrl:   'https://my-bucket.fra1.cdn.digitaloceanspaces.com',
        ),
    ),
);