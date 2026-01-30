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
use PDOException;
use RuntimeException;

final class MySQLTestHelper
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $env = getenv('APP_ENV') ?: 'unknown';

        if ($env !== 'testing') {
            throw new RuntimeException(
                'MySQLTestHelper can only be used when APP_ENV=testing. ' .
                'Current environment: ' . $env
            );
        }

        $host = getenv('DB_HOST');
        $name = getenv('DB_NAME');
        $user = getenv('DB_USER');
        $pass = getenv('DB_PASS');

        if ($host === false || $name === false || $user === false) {
             throw new RuntimeException('Database environment variables (DB_HOST, DB_NAME, DB_USER) are not configured fully.');
        }

        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            $host,
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

        self::bootstrapDatabase(self::$pdo);

        return self::$pdo;
    }

    private static function bootstrapDatabase(PDO $pdo): void
    {
        $schemaPath = __DIR__ . '/../../database/schema.sql';
        if (!file_exists($schemaPath)) {
            throw new RuntimeException('Schema file not found: ' . $schemaPath);
        }

        $sql = file_get_contents($schemaPath);
        if ($sql === false) {
            throw new RuntimeException('Failed to read schema file: ' . $schemaPath);
        }

        try {
            // Disable FK checks during bootstrap
            $pdo->exec('SET FOREIGN_KEY_CHECKS=0');

            // Execute schema as-is (MySQL native)
            $pdo->exec($sql);

            $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
        } catch (PDOException $e) {
            throw new RuntimeException(
                "SQL Error in bootstrap while executing schema.sql\n" .
                "Error: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    public static function truncate(string $table): void
    {
        $env = getenv('APP_ENV') ?: 'unknown';

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
