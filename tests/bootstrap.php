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
    $dotenv->safeLoad();
} elseif (file_exists($rootPath .  '/.env')) {
    $dotenv = Dotenv::createImmutable($rootPath, '.env');
    $dotenv->safeLoad();
}

// 2) Set DEFAULT test environment if not present
// This ensures tests run even if .env is missing/incomplete in sandbox
$defaults = [
    'ADMIN_APP_ENV' => 'testing',
    'ADMIN_APP_DEBUG' => 'true',
    'ADMIN_APP_NAME' => 'AdminPanelTest',
    'ADMIN_URL' => 'http://localhost',
    'ADMIN_DB_HOST' => '127.0.0.1',
    'ADMIN_DB_NAME' => 'test_db',
    'ADMIN_DB_USER' => 'root',
    'ADMIN_DB_PASS' => 'dummy',
    'ADMIN_PASSWORD_PEPPERS' => '{"1": "test-pepper-secret-must-be-32-chars-long"}',
    'ADMIN_PASSWORD_ACTIVE_PEPPER_ID' => '1',
    'ADMIN_PASSWORD_ARGON2_OPTIONS' => '{"memory_cost": 1024, "time_cost": 2, "threads": 2}',
    'ADMIN_EMAIL_BLIND_INDEX_KEY' => 'test-blind-index-key-32-chars-exactly!!',
    'ADMIN_APP_TIMEZONE' => 'Africa/Cairo',
    'ADMIN_MAIL_HOST' => 'smtp.example.com',
    'ADMIN_MAIL_PORT' => '1025',
    'ADMIN_MAIL_USERNAME' => 'test',
    'ADMIN_MAIL_PASSWORD' => 'test',
    'ADMIN_MAIL_FROM_ADDRESS' => 'admin@example.com',
    'ADMIN_MAIL_FROM_NAME' => 'Admin Panel',
    'ADMIN_CRYPTO_KEYS' => '[{"id": "1", "key": "0000000000000000000000000000000000000000000000000000000000000000"}]',
    'ADMIN_CRYPTO_ACTIVE_KEY_ID' => '1',
    'ADMIN_TOTP_ISSUER' => 'AdminPanelTest',
    'ADMIN_TOTP_ENROLLMENT_TTL_SECONDS' => '3600',
    'ADMIN_RECOVERY_MODE' => 'false',
];

foreach ($defaults as $key => $value) {
    if (!array_key_exists($key, $_ENV)) {
        $_ENV[$key] = $value;
        putenv("$key=$value");
    }
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
$envRaw = $_ENV['ADMIN_APP_ENV'] ?? null;
if ($envRaw === null) {
    $envRaw = getenv('ADMIN_APP_ENV');
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
