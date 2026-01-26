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
        $forceSqlite = getenv('TEST_FORCE_SQLITE');

        if ($forceSqlite || $host === false || $host === '') {
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

             self::$pdo->sqliteCreateFunction('NOW', function () {
                 return date('Y-m-d H:i:s');
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
            CREATE TABLE IF NOT EXISTS roles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(64) NOT NULL UNIQUE,
                is_active TINYINT NOT NULL DEFAULT 1,
                display_name VARCHAR(128) NULL,
                description VARCHAR(255) NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );
SQL
        );

        $pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS permissions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(64) NOT NULL UNIQUE,
                display_name VARCHAR(128) NULL,
                description VARCHAR(255) NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );
SQL
        );

        $pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS role_permissions (
                role_id INTEGER NOT NULL,
                permission_id INTEGER NOT NULL,
                PRIMARY KEY (role_id, permission_id)
            );
SQL
        );

        $pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS admin_direct_permissions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                admin_id INTEGER NOT NULL,
                permission_id INTEGER NOT NULL,
                is_allowed TINYINT NOT NULL,
                granted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                expires_at DATETIME NULL
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

        $pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS admins (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                display_name VARCHAR(100) NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );
SQL
        );

        $pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS admin_emails (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                admin_id INTEGER NOT NULL,
                email_blind_index CHAR(64) NOT NULL,
                email_ciphertext BLOB NOT NULL,
                email_iv BLOB NOT NULL,
                email_tag BLOB NOT NULL,
                email_key_id VARCHAR(64) NOT NULL,
                verification_status VARCHAR(20) NOT NULL DEFAULT 'pending',
                verified_at DATETIME NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
SQL
        );

        $pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS admin_passwords (
                admin_id INTEGER PRIMARY KEY,
                password_hash VARCHAR(255) NOT NULL,
                pepper_id VARCHAR(16) NOT NULL,
                must_change_password TINYINT NOT NULL DEFAULT 0
            );
SQL
        );

        $pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS admin_sessions (
                session_id VARCHAR(64) PRIMARY KEY,
                admin_id INTEGER NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                expires_at DATETIME NOT NULL,
                is_revoked TINYINT NOT NULL DEFAULT 0,
                pending_totp_seed_ciphertext BLOB NULL,
                pending_totp_seed_iv BLOB NULL,
                pending_totp_seed_tag BLOB NULL,
                pending_totp_seed_key_id VARCHAR(64) NULL,
                pending_totp_issued_at DATETIME NULL
            );
SQL
        );

        $pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS admin_roles (
                admin_id INTEGER NOT NULL,
                role_id INTEGER NOT NULL,
                PRIMARY KEY (admin_id, role_id)
            );
SQL
        );

        $pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS system_ownership (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                admin_id INTEGER NOT NULL UNIQUE,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );
SQL
        );

        $pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS system_state (
                state_key VARCHAR(64) PRIMARY KEY,
                state_value VARCHAR(64) NOT NULL,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
SQL
        );

        $pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS step_up_grants (
                admin_id INTEGER NOT NULL,
                session_id VARCHAR(64) NOT NULL,
                scope VARCHAR(64) NOT NULL,
                risk_context_hash VARCHAR(64) NOT NULL,
                issued_at DATETIME NOT NULL,
                expires_at DATETIME NOT NULL,
                single_use TINYINT NOT NULL DEFAULT 0,
                context_snapshot TEXT NULL,
                PRIMARY KEY (admin_id, session_id, scope)
            );
SQL
        );

        $pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS admin_totp_secrets (
                admin_id INTEGER NOT NULL,
                seed_ciphertext BLOB NOT NULL,
                seed_iv BLOB NOT NULL,
                seed_tag BLOB NOT NULL,
                seed_key_id VARCHAR(64) NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                rotated_at DATETIME NULL DEFAULT NULL,
                PRIMARY KEY (admin_id)
            );
SQL
        );

        $pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS audit_outbox (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                actor_type VARCHAR(32) NULL, -- Nullable to support legacy writer
                actor_id INTEGER NULL,
                action VARCHAR(128) NOT NULL,
                target_type VARCHAR(64) NOT NULL,
                target_id INTEGER NULL,
                risk_level VARCHAR(16) NOT NULL,
                payload TEXT NOT NULL,
                correlation_id CHAR(36) NOT NULL,
                created_at DATETIME NOT NULL
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
