<?php

declare(strict_types=1);

namespace Tests\Integration\I18n;

use Tests\Support\I18nMigrationSupport;
use Tests\Support\I18nSchemaFingerprint;
use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;

/**
 * Forward migration proof for the nullable translation `type` column
 * (maatify/php-i18n ADR-020), on a real database:
 *
 *   - a canonical `maa_i18n_*` database WITHOUT the column (the shape before the
 *     package gained `type`) is migrated with the real Host migration file;
 *   - the result is structurally identical to a fresh install of the package schema;
 *   - existing rows keep every value and receive NULL as their type;
 *   - the package's check constraint is in force (a whitespace-only type is refused).
 */
final class I18nTranslationTypeMigrationIntegrationTest extends TestCase
{
    private const MIGRATION = '/database/migrations/20261003_000001_maa_i18n_translations_type.sql';
    private const PACKAGE_SCHEMA = '/vendor/maatify/php-i18n/schema/schema.i18n.sql';

    private static ?PDO $pdo = null;

    /** @var list<string> */
    private array $schemas = [];

    public static function setUpBeforeClass(): void
    {
        $credentials = I18nMigrationSupport::credentialsFromEnv();
        if ($credentials === null) {
            if (I18nMigrationSupport::isRequired()) {
                self::fail('ADMIN_I18N_MIGRATION_IT_REQUIRED=1 but ADMIN_DB_* credentials are not set.');
            }
            self::markTestSkipped('ADMIN_DB_* env credentials not set — integration tests are opt-in.');
        }

        try {
            self::$pdo = I18nMigrationSupport::connect($credentials);
        } catch (PDOException $e) {
            if (I18nMigrationSupport::isRequired()) {
                self::fail('Required MySQL is unreachable: ' . $e->getMessage());
            }
            self::markTestSkipped('MySQL is not reachable: ' . $e->getMessage());
        }
    }

    public static function tearDownAfterClass(): void
    {
        self::$pdo = null;
    }

    protected function tearDown(): void
    {
        foreach ($this->schemas as $schema) {
            self::$pdo?->exec('DROP DATABASE IF EXISTS `' . $schema . '`');
        }
        $this->schemas = [];
    }

    public function testTheMigratedStructureEqualsAFreshInstallOfThePackageSchema(): void
    {
        $this->newSchema('i18n_type_mig');
        $this->createStateBeforeType();
        $this->runMigration();
        $migrated = I18nSchemaFingerprint::of($this->pdo());

        $this->newSchema('i18n_type_fresh');
        $this->pdo()->exec($this->file(self::PACKAGE_SCHEMA));
        $fresh = I18nSchemaFingerprint::of($this->pdo());

        self::assertSame($fresh, $migrated);
        self::assertGreaterThan(40, count($fresh));
    }

    public function testExistingRowsKeepTheirValuesAndReceiveANullType(): void
    {
        $this->newSchema('i18n_type_rows');
        $this->createStateBeforeType();

        $pdo = $this->pdo();
        $pdo->exec("INSERT INTO maa_i18n_scopes (code, name, is_active, sort_order) VALUES ('ct', 'CT', 1, 1)");
        $pdo->exec("INSERT INTO maa_i18n_domains (code, name, is_active, sort_order) VALUES ('home', 'Home', 1, 1)");
        $pdo->exec("INSERT INTO maa_i18n_domain_scopes (scope_code, domain_code) VALUES ('ct', 'home')");
        $pdo->exec("INSERT INTO maa_i18n_keys (scope, domain, key_part) VALUES ('ct', 'home', 'title')");
        $keyId = (int) $pdo->lastInsertId();
        $pdo->exec("INSERT INTO maa_i18n_translations (key_id, language_code, value) VALUES ($keyId, 'ar', 'مرحبا'), ($keyId, NULL, ''), ($keyId, 'AR', 'upper')");
        $before = $this->rows('SELECT id, key_id, language_code, value FROM maa_i18n_translations ORDER BY id');
        self::assertCount(3, $before);

        $this->runMigration();

        self::assertSame(
            $before,
            $this->rows('SELECT id, key_id, language_code, value FROM maa_i18n_translations ORDER BY id'),
            'migration must not change identity, language scope or value'
        );
        self::assertSame(
            [['type' => null], ['type' => null], ['type' => null]],
            $this->rows('SELECT type FROM maa_i18n_translations ORDER BY id')
        );
    }

    public function testThePackageCheckConstraintIsInForceAfterTheMigration(): void
    {
        $this->newSchema('i18n_type_check');
        $this->createStateBeforeType();
        $this->runMigration();

        $pdo = $this->pdo();
        $pdo->exec("INSERT INTO maa_i18n_scopes (code, name, is_active, sort_order) VALUES ('ct', 'CT', 1, 1)");
        $pdo->exec("INSERT INTO maa_i18n_domains (code, name, is_active, sort_order) VALUES ('home', 'Home', 1, 1)");
        $pdo->exec("INSERT INTO maa_i18n_domain_scopes (scope_code, domain_code) VALUES ('ct', 'home')");
        $pdo->exec("INSERT INTO maa_i18n_keys (scope, domain, key_part) VALUES ('ct', 'home', 'title')");
        $keyId = (int) $pdo->lastInsertId();

        $pdo->exec("INSERT INTO maa_i18n_translations (key_id, language_code, value, type) VALUES ($keyId, 'ar', 'x', 'client.rich-content')");
        self::assertSame('client.rich-content', $pdo->query('SELECT type FROM maa_i18n_translations')->fetchColumn());

        $this->expectException(PDOException::class);
        $pdo->exec("INSERT INTO maa_i18n_translations (key_id, language_code, value, type) VALUES ($keyId, 'en', 'y', '   ')");
    }

    /**
     * A canonical database as it was before the package gained `type`: the package
     * schema with the column and its constraint removed again.
     */
    private function createStateBeforeType(): void
    {
        $pdo = $this->pdo();
        $pdo->exec($this->file(self::PACKAGE_SCHEMA));
        $pdo->exec('ALTER TABLE maa_i18n_translations DROP CONSTRAINT chk_maa_i18n_translations_type');
        $pdo->exec('ALTER TABLE maa_i18n_translations DROP COLUMN type');

        self::assertSame(
            0,
            (int) $pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'maa_i18n_translations' AND COLUMN_NAME = 'type'")->fetchColumn()
        );
    }

    private function runMigration(): void
    {
        $lines = array_filter(
            explode("\n", $this->file(self::MIGRATION)),
            static fn (string $line): bool => !str_starts_with(ltrim($line), '--')
        );

        foreach (explode(";\n", implode("\n", $lines) . "\n") as $statement) {
            $statement = trim($statement);
            if ($statement !== '') {
                $this->pdo()->exec($statement);
            }
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function rows(string $sql): array
    {
        $stmt = $this->pdo()->query($sql);
        self::assertNotFalse($stmt);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function newSchema(string $prefix): void
    {
        $name = I18nMigrationSupport::randomSchemaName($prefix);
        $this->pdo()->exec('CREATE DATABASE `' . $name . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->pdo()->exec('USE `' . $name . '`');
        $this->schemas[] = $name;
    }

    private function file(string $relative): string
    {
        $content = file_get_contents(dirname(__DIR__, 4) . $relative);
        self::assertIsString($content, $relative);

        return $content;
    }

    private function pdo(): PDO
    {
        self::assertNotNull(self::$pdo);

        return self::$pdo;
    }
}
