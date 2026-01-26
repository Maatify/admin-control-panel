<?php

/**
 * @copyright   ©2025 Maatify.dev
 * @Library     maatify/data-repository
 * @Project     maatify:data-repository
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2025-11-25 00:00:00
 * @see         https://www.maatify.dev
 * @link        https://github.com/Maatify/data-repository
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

// ------------------------------------------------------------
use Dotenv\Dotenv;

$rootPath = dirname(__DIR__);

// Prefer .env.test if exists, fallback to .env
if (file_exists($rootPath . '/.env.test')) {
    $dotenv = Dotenv::createImmutable($rootPath, '.env.test');
} elseif (file_exists($rootPath .  '/.env')) {
    $dotenv = Dotenv::createImmutable($rootPath, '.env');
} else {
    $dotenv = null;
}

if ($dotenv !== null) {
    // safeLoad = no exception if file exists but vars already defined
    $dotenv->safeLoad();
}

// ------------------------------------------------------------
// 3) Sync $_ENV into putenv() (important for PDO / legacy libs)
// ------------------------------------------------------------
foreach ($_ENV as $key => $value) {
    if (is_scalar($value)) {
        putenv($key . '=' . (string) $value);
    }
}

// ------------------------------------------------------------
// 4) Normalize environment value (PHPStan-safe)
// ------------------------------------------------------------
/** @var mixed $envRaw */
$envRaw = $_ENV['APP_ENV'] ?? null;
if ($envRaw === null) {
    $envRaw = getenv('APP_ENV');
}
if ($envRaw === false) {
    $envRaw = 'unknown';
}

$envString = is_scalar($envRaw)
    ? (string) $envRaw
    : 'unknown';

// ------------------------------------------------------------
// 5) Display current environment (deterministic, optional)
// ------------------------------------------------------------
echo '🧪 Environment: ' . $envString . PHP_EOL;

// ------------------------------------------------------------
// 6) Optional: Disable output buffering for CI
// ------------------------------------------------------------
if (function_exists('ini_set')) {
    ini_set('output_buffering', 'off');
    ini_set('implicit_flush', '1');
}