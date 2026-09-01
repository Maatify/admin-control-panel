<?php

declare(strict_types=1);

/**
 * Bootstrap file for Storage Module tests
 *
 * Sets up the test environment and autoloading
 */

// Load Composer autoloader
// This module is nested at: project/Modules/Storage/tests/bootstrap.php
// Composer vendor is at: project/vendor/autoload.php
$projectRoot = dirname(__DIR__, 3);
$composerAutoload = $projectRoot . '/vendor/autoload.php';

if (!file_exists($composerAutoload)) {
    // Try alternative path if module is in different location
    $composerAutoload = dirname(__DIR__, 4) . '/vendor/autoload.php';
}

if (!file_exists($composerAutoload)) {
    throw new RuntimeException('Composer autoloader not found. Tried: ' . $projectRoot . '/vendor/autoload.php');
}

require_once $composerAutoload;

// Define test fixtures path
define('STORAGE_TESTS_FIXTURES_PATH', __DIR__ . '/Fixtures');
define('STORAGE_TESTS_TEMP_PATH', sys_get_temp_dir() . '/storage-module-tests');

// Ensure temp directory exists
if (!is_dir(STORAGE_TESTS_TEMP_PATH)) {
    mkdir(STORAGE_TESTS_TEMP_PATH, 0777, true);
}

// Register test namespace for test classes
spl_autoload_register(static function (string $class): void {
    if (strpos($class, 'Maatify\\Storage\\Tests\\') !== 0) {
        return;
    }

    $relativePath = substr($class, strlen('Maatify\\Storage\\Tests\\'));
    $path = __DIR__ . '/' . str_replace('\\', '/', $relativePath) . '.php';

    if (file_exists($path)) {
        require_once $path;
    }
}, true, true);
