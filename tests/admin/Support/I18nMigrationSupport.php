<?php

declare(strict_types=1);

namespace Tests\Support;

use PDO;

/**
 * Connection helper for the I18n forward-migration integration tests, which create (and
 * drop) their own isolated databases on the same server as the Admin test database.
 *
 * Credentials: ADMIN_MIGRATION_IT_DB_{HOST,PORT,USER,PASS} when set (a user allowed to
 * CREATE DATABASE, e.g. root in CI, where ADMIN_DB_USER is limited to one database), otherwise
 * the Admin test credentials (ADMIN_DB_*). Without usable credentials, or when the user cannot
 * create databases, the tests skip; with ADMIN_I18N_MIGRATION_IT_REQUIRED=1 they FAIL instead.
 */
final class I18nMigrationSupport
{
    /**
     * @return array{host: string, port: int|null, user: string, pass: string}|null
     */
    public static function credentialsFromEnv(): ?array
    {
        $dedicated = self::env('ADMIN_MIGRATION_IT_DB_USER') !== null;
        $prefix = $dedicated ? 'ADMIN_MIGRATION_IT_DB_' : 'ADMIN_DB_';

        $host = self::env($prefix . 'HOST') ?? self::env('ADMIN_DB_HOST');
        $user = self::env($prefix . 'USER');
        $pass = self::env($prefix . 'PASS') ?? '';

        if ($host === null || $user === null) {
            return null;
        }

        $portRaw = self::env($prefix . 'PORT') ?? self::env('ADMIN_DB_PORT');
        $port = ($portRaw !== null && is_numeric($portRaw)) ? (int) $portRaw : null;

        return ['host' => $host, 'port' => $port, 'user' => $user, 'pass' => $pass];
    }

    public static function isRequired(): bool
    {
        return self::env('ADMIN_I18N_MIGRATION_IT_REQUIRED') === '1';
    }

    public static function randomSchemaName(string $prefix): string
    {
        return $prefix . '_' . bin2hex(random_bytes(4));
    }

    /**
     * @param array{host: string, port: int|null, user: string, pass: string} $credentials
     */
    public static function connect(array $credentials, ?string $schema = null): PDO
    {
        $dsn = 'mysql:host=' . $credentials['host']
            . ($credentials['port'] !== null ? ';port=' . $credentials['port'] : '')
            . ($schema !== null ? ';dbname=' . $schema : '');

        $pdo = new PDO($dsn, $credentials['user'], $credentials['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
        ]);

        if ($schema === null) {
            // Server-level connection: the tests create isolated databases, so prove the user may.
            $probe = self::randomSchemaName('i18n_mig_probe');
            try {
                $pdo->exec('CREATE DATABASE `' . $probe . '`');
                $pdo->exec('DROP DATABASE `' . $probe . '`');
            } catch (\PDOException $e) {
                throw new \PDOException('The configured database user cannot CREATE DATABASE: ' . $e->getMessage(), 0, $e);
            }
        }

        return $pdo;
    }

    private static function env(string $key): ?string
    {
        $value = getenv($key);

        return ($value === false || $value === '') ? null : $value;
    }
}
