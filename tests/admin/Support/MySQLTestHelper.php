<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-11 20:18
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Tests\Support;

use PDO;
use RuntimeException;

final class MySQLTestHelper
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $env = getenv('ADMIN_APP_ENV') ?: 'unknown';

        if ($env !== 'testing') {
            throw new RuntimeException(
                'MySQLTestHelper can only be used when ADMIN_APP_ENV=testing. ' .
                'Current environment: ' . $env
            );
        }

        $host = getenv('ADMIN_DB_HOST');
        $port = getenv('ADMIN_DB_PORT') ?: '3306';
        $name = getenv('ADMIN_DB_NAME');
        $user = getenv('ADMIN_DB_USER');
        $pass = getenv('ADMIN_DB_PASS');

        if ($host === false || $name === false || $user === false) {
             throw new RuntimeException('Database environment variables (ADMIN_DB_HOST, ADMIN_DB_NAME, ADMIN_DB_USER) are not configured fully.');
        }

        if (!str_ends_with($name, '_test')) {
            throw new RuntimeException('Refusing to run Admin integration tests against a database without a _test suffix.');
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $host,
            $port,
            $name
        );

        self::$pdo = new PDO(
            $dsn,
            $user,
            $pass ?: null,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );

        return self::$pdo;
    }

    public static function truncate(string $table): void
    {
        $env = getenv('ADMIN_APP_ENV') ?: 'unknown';

        if ($env !== 'testing') {
            throw new RuntimeException(
                'Refusing to truncate table outside testing environment.'
            );
        }

        $pdo = self::pdo();
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        $pdo->exec('TRUNCATE TABLE ' . $table);
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    }
}
