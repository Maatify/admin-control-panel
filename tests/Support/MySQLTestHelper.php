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

        $env = getenv('APP_ENV') ?: 'unknown';

        if ($env !== 'testing') {
            throw new RuntimeException(
                'MySQLTestHelper can only be used when APP_ENV=testing. ' .
                'Current environment: ' . $env
            );
        }

        $host = getenv('DB_HOST');

        if ($host === false || $host === '') {
             // Fallback to SQLite in-memory
             self::$pdo = new PDO('sqlite::memory:');
             self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
             self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

             self::$pdo->sqliteCreateFunction('IF', function ($condition, $true, $false) {
                 return $condition ? $true : $false;
             });

             self::$pdo->sqliteCreateFunction('JSON_LENGTH', function ($json) {
                 if ($json === null || $json === '') {
                     return null;
                 }
                 $data = json_decode($json, true);
                 return is_array($data) ? count($data) : 0;
             });

             self::bootstrapDatabase(self::$pdo);
             return self::$pdo;
        }

        $name = getenv('DB_NAME');
        $user = getenv('DB_USER');
        $pass = getenv('DB_PASS');

        if ($name === false || $user === false) {
             throw new RuntimeException('Database environment variables are not configured fully (DB_HOST present but others missing).');
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

        return self::$pdo;
    }

    private static function bootstrapDatabase(PDO $pdo): void
    {
        // Minimal schema for tests
        $pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS activity_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                actor_type VARCHAR(32) NOT NULL,
                actor_id INTEGER NULL,
                action VARCHAR(128) NOT NULL,
                entity_type VARCHAR(64) NULL,
                entity_id INTEGER NULL,
                metadata TEXT NULL,
                ip_address VARCHAR(45) NULL,
                user_agent VARCHAR(255) NULL,
                request_id VARCHAR(64) NULL,
                occurred_at DATETIME NOT NULL
            );
SQL
        );

        $pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS security_events (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                actor_type VARCHAR(32) NOT NULL CHECK(length(actor_type) <= 32),
                actor_id INTEGER NULL,
                event_type VARCHAR(100) NOT NULL,
                severity VARCHAR(20) NOT NULL,
                request_id VARCHAR(64) NULL,
                route_name VARCHAR(255) NULL,
                ip_address VARCHAR(45) NULL,
                user_agent TEXT NULL,
                metadata TEXT NOT NULL,
                occurred_at DATETIME NOT NULL
            );
SQL
        );

        $pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS telemetry_traces (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                event_key VARCHAR(255) NOT NULL,
                severity VARCHAR(20) NOT NULL,
                route_name VARCHAR(255) NULL,
                request_id VARCHAR(64) NULL,
                actor_type VARCHAR(32) NOT NULL,
                actor_id INTEGER NULL,
                ip_address VARCHAR(45) NULL,
                user_agent TEXT NULL,
                metadata TEXT NULL,
                occurred_at DATETIME NOT NULL
            );
SQL
        );
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
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            $pdo->exec('DELETE FROM ' . $table);
            // Optional: Reset sequence
            $pdo->exec("DELETE FROM sqlite_sequence WHERE name='$table'");
        } else {
            $pdo->exec('TRUNCATE TABLE ' . $table);
        }
    }
}
