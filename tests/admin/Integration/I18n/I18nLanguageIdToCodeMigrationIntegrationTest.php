<?php

declare(strict_types=1);

namespace Tests\Integration\I18n;

use Tests\Support\I18nMigrationSupport;
use Tests\Support\I18nSchemaFingerprint;
use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;

/**
 * Real-database proof of the ADR-019 data migration
 * (database/migrations/20260930_000001_i18n_translations_language_id_to_language_code.sql).
 *
 * The OLD (language_id) shape is created inline, populated, migrated with the
 * real migration file, and compared with the source data.
 */
final class I18nLanguageIdToCodeMigrationIntegrationTest extends TestCase
{
    private const MIGRATION = '/database/migrations/20260930_000001_i18n_translations_language_id_to_language_code.sql';
    private const NAMESPACE_MIGRATION = '/database/migrations/20261001_000001_i18n_tables_to_maa_i18n_namespace.sql';
    private const TYPE_MIGRATION = '/database/migrations/20261003_000001_maa_i18n_translations_type.sql';
    private const PACKAGE_SCHEMA = '/vendor/maatify/php-i18n/schema/schema.i18n.sql';

    private static ?PDO $pdo = null;
    /** @var array{host: string, port: int|null, user: string, pass: string}|null */
    private static ?array $credentials = null;

    private string $schema = '';

    public static function setUpBeforeClass(): void
    {
        self::$credentials = I18nMigrationSupport::credentialsFromEnv();
        if (self::$credentials === null) {
            if (I18nMigrationSupport::isRequired()) {
                self::fail('ADMIN_I18N_MIGRATION_IT_REQUIRED=1 but ADMIN_DB_* credentials are not set.');
            }
            self::markTestSkipped('ADMIN_DB_* env credentials not set — integration tests are opt-in.');
        }

        try {
            self::$pdo = I18nMigrationSupport::connect(self::$credentials);
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

    protected function setUp(): void
    {
        $pdo = self::$pdo;
        self::assertInstanceOf(PDO::class, $pdo);

        $this->schema = I18nMigrationSupport::randomSchemaName('i18n_migr');
        $pdo->exec('CREATE DATABASE `' . $this->schema . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $pdo->exec('USE `' . $this->schema . '`');
    }

    protected function tearDown(): void
    {
        self::$pdo?->exec('DROP DATABASE IF EXISTS `' . $this->schema . '`');
    }

    public function testMigrationPreservesEveryTranslationAndRebuildsTheSummaryFromAuthoritativeData(): void
    {
        $pdo = $this->pdo();
        $this->createOldSchema(uniqueLanguageCode: true);

        $pdo->exec("INSERT INTO languages (id, code) VALUES (1, 'ar'), (2, 'en'), (7, 'fr')");
        $pdo->exec("INSERT INTO i18n_keys (id, scope, domain, key_part) VALUES (10, 'ct', 'home', 'a'), (11, 'ct', 'home', 'b'), (12, 'ct', 'cart', 'c')");
        $pdo->exec(
            "INSERT INTO i18n_translations (id, key_id, language_id, value, created_at, updated_at) VALUES
                (100, 10, 1, 'أ', '2026-01-01 00:00:00', NULL),
                (101, 10, 2, 'a', '2026-01-02 00:00:00', '2026-01-03 00:00:00'),
                (102, 11, 1, 'ب', '2026-01-04 00:00:00', NULL),
                (103, 12, 2, 'c', '2026-01-05 00:00:00', NULL)"
        );
        // Deliberately STALE derived data: the migration must not trust it.
        $pdo->exec("INSERT INTO i18n_domain_language_summary (scope, domain, language_id, total_keys, translated_count, missing_count) VALUES ('ct', 'home', 1, 99, 99, 0)");

        $before = $this->translationFingerprint("SELECT t.id, t.key_id, l.code AS language_code, t.value, t.created_at, t.updated_at
            FROM i18n_translations t JOIN languages l ON l.id = t.language_id ORDER BY t.id");

        $this->runMigration();

        $after = $this->translationFingerprint('SELECT id, key_id, language_code, value, created_at, updated_at FROM i18n_translations ORDER BY id');
        self::assertSame($before, $after, 'ids, keys, exact codes, values and timestamps must all be preserved');
        self::assertCount(4, $after);

        // Old identity is gone, no Host FK remains.
        self::assertSame(0, $this->scalarInt("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name IN ('i18n_translations','i18n_domain_language_summary') AND column_name = 'language_id'"));
        self::assertSame(0, $this->scalarInt("SELECT COUNT(*) FROM information_schema.referential_constraints WHERE constraint_schema = DATABASE() AND referenced_table_name = 'languages'"));

        // Summary rebuilt from authoritative tables: exact-scope rows only, no row for 'fr'.
        $summary = $this->translationFingerprint('SELECT scope, domain, language_code, total_keys, translated_count, missing_count
            FROM i18n_domain_language_summary ORDER BY scope, domain, language_code');
        self::assertSame(
            [
                ['scope' => 'ct', 'domain' => 'cart', 'language_code' => 'en', 'total_keys' => '1', 'translated_count' => '1', 'missing_count' => '0'],
                ['scope' => 'ct', 'domain' => 'home', 'language_code' => 'ar', 'total_keys' => '2', 'translated_count' => '2', 'missing_count' => '0'],
                ['scope' => 'ct', 'domain' => 'home', 'language_code' => 'en', 'total_keys' => '2', 'translated_count' => '1', 'missing_count' => '1'],
            ],
            $summary
        );

        // The temporary legacy-mapping guard is gone again.
        self::assertSame(0, $this->scalarInt("SELECT COUNT(*) FROM information_schema.table_constraints WHERE table_schema = DATABASE() AND constraint_name = 'chk_i18n_translations_legacy_mapped'"));

        // New identity is enforced NULL-safely.
        $pdo->exec("INSERT INTO i18n_translations (key_id, language_code, value) VALUES (11, NULL, 'n')");
        $this->expectException(PDOException::class);
        $pdo->exec("INSERT INTO i18n_translations (key_id, language_code, value) VALUES (11, NULL, 'dup')");
    }

    /**
     * The complete upgrade path a real legacy database takes in deployment: the OLD
     * language_id shape -> ADR-019 language_code -> canonical maa_i18n_* namespace ->
     * nullable translation type. Proves the three migrations compose: the data survives
     * every step and the end state equals a fresh install of the package schema.
     */
    public function testTheThreeMigrationsComposeFromTheOldShapeToThePackageSchemaWithoutLosingData(): void
    {
        $pdo = $this->pdo();
        $this->createFaithfulOldSchema();

        $pdo->exec("INSERT INTO languages (id, name, code) VALUES (1, 'Arabic', 'ar'), (2, 'English', 'en')");
        $pdo->exec("INSERT INTO i18n_scopes (code, name) VALUES ('ct', 'Website')");
        $pdo->exec("INSERT INTO i18n_domains (code, name) VALUES ('home', 'Home')");
        $pdo->exec("INSERT INTO i18n_domain_scopes (scope_code, domain_code) VALUES ('ct', 'home')");
        $pdo->exec("INSERT INTO i18n_keys (id, scope, domain, key_part) VALUES (10, 'ct', 'home', 'a'), (11, 'ct', 'home', 'b')");
        $pdo->exec(
            "INSERT INTO i18n_translations (id, key_id, language_id, value, created_at, updated_at) VALUES
                (100, 10, 1, 'أ', '2026-01-01 00:00:00', NULL),
                (101, 10, 2, 'a', '2026-01-02 00:00:00', '2026-01-03 00:00:00'),
                (102, 11, 1, '', '2026-01-04 00:00:00', NULL)"
        );
        $pdo->exec("INSERT INTO i18n_key_stats (key_id, translated_count) VALUES (10, 2), (11, 1)");

        $before = $this->translationFingerprint("SELECT t.id, t.key_id, l.code AS language_code, t.value, t.created_at, t.updated_at
            FROM i18n_translations t JOIN languages l ON l.id = t.language_id ORDER BY t.id");
        self::assertCount(3, $before);

        foreach ([self::MIGRATION, self::NAMESPACE_MIGRATION, self::TYPE_MIGRATION] as $file) {
            $this->runMigrationFile($file);
        }

        // No legacy table survives; the canonical set exists.
        self::assertSame(0, $this->scalarInt("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name LIKE 'i18n\\_%'"));
        self::assertSame(7, $this->scalarInt("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name LIKE 'maa\\_i18n\\_%'"));

        // Every translation: same id, key, exact code, value (incl. the empty one) and timestamps; type NULL.
        $after = $this->translationFingerprint('SELECT id, key_id, language_code, value, created_at, updated_at FROM maa_i18n_translations ORDER BY id');
        self::assertSame($before, $after);
        self::assertSame(3, $this->scalarInt('SELECT COUNT(*) FROM maa_i18n_translations WHERE type IS NULL'));

        // Governance data and key counters survive the rename.
        self::assertSame(1, $this->scalarInt("SELECT COUNT(*) FROM maa_i18n_scopes WHERE code = 'ct'"));
        self::assertSame(1, $this->scalarInt("SELECT COUNT(*) FROM maa_i18n_domain_scopes WHERE scope_code = 'ct' AND domain_code = 'home'"));
        self::assertSame(2, $this->scalarInt('SELECT COUNT(*) FROM maa_i18n_keys'));
        self::assertSame(3, $this->scalarInt('SELECT SUM(translated_count) FROM maa_i18n_key_stats'));

        $migrated = array_values(array_filter(I18nSchemaFingerprint::of($pdo), static fn (string $line): bool => str_contains($line, 'maa_i18n_')));

        // The `languages` table belongs to the Host and is outside the package fingerprint.
        $fresh = $this->freshPackageFingerprint();
        self::assertSame($fresh, $migrated, 'the fully migrated database must equal a fresh install of the package schema');
        self::assertGreaterThan(40, count($fresh));
    }

    public function testMigrationAbortsBeforeTheDestructiveSwitchWhenTwoRowsWouldCollapse(): void
    {
        $pdo = $this->pdo();
        // Two Host languages sharing one code (only possible when the Host
        // uniqueness is missing) would collapse into one (key_id, code).
        $this->createOldSchema(uniqueLanguageCode: false);

        $pdo->exec("INSERT INTO languages (id, code) VALUES (1, 'ar'), (2, 'ar')");
        $pdo->exec("INSERT INTO i18n_keys (id, scope, domain, key_part) VALUES (10, 'ct', 'home', 'a')");
        $pdo->exec("INSERT INTO i18n_translations (id, key_id, language_id, value) VALUES (100, 10, 1, 'one'), (101, 10, 2, 'two')");

        try {
            $this->runMigration();
            self::fail('The migration must fail on the identity collapse.');
        } catch (PDOException) {
            self::assertTrue(true);
        }

        // Nothing destructive happened: the old identity and both rows survive.
        self::assertSame(1, $this->scalarInt("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'i18n_translations' AND column_name = 'language_id'"));
        self::assertSame(2, $this->scalarInt('SELECT COUNT(*) FROM i18n_translations'));
        self::assertSame(2, $this->scalarInt('SELECT COUNT(DISTINCT language_id) FROM i18n_translations'));
    }

    public function testMigrationAbortsBeforeTheDestructiveSwitchWhenALegacyRowHasNoHostLanguage(): void
    {
        $pdo = $this->pdo();
        $this->createOldSchema(uniqueLanguageCode: true);

        $pdo->exec("INSERT INTO languages (id, code) VALUES (1, 'ar')");
        $pdo->exec("INSERT INTO i18n_keys (id, scope, domain, key_part) VALUES (10, 'ct', 'home', 'a')");
        // Orphan language_id 99 (possible after an import with FK checks off).
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        $pdo->exec("INSERT INTO i18n_translations (id, key_id, language_id, value) VALUES (100, 10, 1, 'ok'), (101, 10, 99, 'orphan')");
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');

        try {
            $this->runMigration();
            self::fail('The migration must refuse an unmapped legacy language_id.');
        } catch (PDOException) {
            self::assertTrue(true);
        }

        // It must NOT have been silently turned into the NULL/unlocalized identity.
        self::assertSame(1, $this->scalarInt("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'i18n_translations' AND column_name = 'language_id'"));
        self::assertSame(1, $this->scalarInt('SELECT COUNT(*) FROM i18n_translations WHERE language_id = 99'));
        self::assertSame(2, $this->scalarInt('SELECT COUNT(*) FROM i18n_translations'));
    }

    // ── helpers ────────────────────────────────────────────────────────────

    private function pdo(): PDO
    {
        self::assertNotNull(self::$pdo);

        return self::$pdo;
    }

    private function createOldSchema(bool $uniqueLanguageCode): void
    {
        $pdo = $this->pdo();
        $unique = $uniqueLanguageCode ? ', UNIQUE KEY uq_languages_code (code)' : '';

        $pdo->exec("CREATE TABLE languages (id INT UNSIGNED NOT NULL PRIMARY KEY, code VARCHAR(16) NOT NULL{$unique}) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $pdo->exec("CREATE TABLE i18n_keys (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            scope VARCHAR(32) NOT NULL, domain VARCHAR(64) NOT NULL, key_part VARCHAR(128) NOT NULL,
            description VARCHAR(255) NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_i18n_keys_identity (scope, domain, key_part)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $pdo->exec("CREATE TABLE i18n_translations (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            key_id BIGINT UNSIGNED NOT NULL,
            language_id INT UNSIGNED NOT NULL,
            value TEXT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_i18n_translation_unique (key_id, language_id),
            KEY idx_i18n_translations_language_id (language_id),
            KEY idx_i18n_translations_key_id (key_id),
            CONSTRAINT fk_i18n_translation_key FOREIGN KEY (key_id) REFERENCES i18n_keys(id) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT fk_i18n_translation_language FOREIGN KEY (language_id) REFERENCES languages(id) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $pdo->exec("CREATE TABLE i18n_domain_language_summary (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            scope VARCHAR(32) NOT NULL, domain VARCHAR(64) NOT NULL, language_id INT UNSIGNED NOT NULL,
            total_keys INT UNSIGNED NOT NULL DEFAULT 0, translated_count INT UNSIGNED NOT NULL DEFAULT 0,
            missing_count INT UNSIGNED NOT NULL DEFAULT 0,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_i18n_domain_language_summary_identity (scope, domain, language_id),
            KEY idx_i18n_domain_language_summary_language (language_id),
            CONSTRAINT fk_i18n_domain_language_summary_language FOREIGN KEY (language_id) REFERENCES languages(id) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    /**
     * Runs the real migration file statement by statement (stops on the first
     * error, exactly like the mysql CLI does).
     */
    private function runMigration(): void
    {
        $sql = file_get_contents(dirname(__DIR__, 4) . self::MIGRATION);
        self::assertIsString($sql);

        $lines = array_filter(
            explode("\n", $sql),
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
     * The database exactly as the Admin installed it BEFORE the migrations: the real languages
     * schema (database/a001.languages.sql) plus the old database/a002.i18n.schema.sql, kept
     * verbatim as tests/admin/Fixtures/i18n_old_language_id_schema.sql (i18n_* tables, translations
     * and summary keyed by language_id with foreign keys to languages).
     */
    private function createFaithfulOldSchema(): void
    {
        foreach (['/database/a001.languages.sql', '/tests/admin/Fixtures/i18n_old_language_id_schema.sql'] as $file) {
            $sql = file_get_contents(dirname(__DIR__, 4) . $file);
            self::assertIsString($sql, $file);
            $this->pdo()->exec($sql);
        }

        self::assertSame(
            1,
            $this->scalarInt("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'i18n_translations' AND column_name = 'language_id'")
        );
    }

    /**
     * Runs one real migration file statement by statement (stops on the first error).
     */
    private function runMigrationFile(string $relative): void
    {
        $sql = file_get_contents(dirname(__DIR__, 4) . $relative);
        self::assertIsString($sql);

        $lines = array_filter(
            explode("\n", $sql),
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
     * @return list<string>
     */
    private function freshPackageFingerprint(): array
    {
        $pdo = $this->pdo();
        $name = I18nMigrationSupport::randomSchemaName('i18n_migr_fresh');
        $pdo->exec('CREATE DATABASE `' . $name . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        try {
            $pdo->exec('USE `' . $name . '`');
            $schema = file_get_contents(dirname(__DIR__, 4) . self::PACKAGE_SCHEMA);
            self::assertIsString($schema);
            $pdo->exec($schema);

            return array_values(array_filter(I18nSchemaFingerprint::of($pdo), static fn (string $line): bool => str_contains($line, 'maa_i18n_')));
        } finally {
            $pdo->exec('USE `' . $this->schema . '`');
            $pdo->exec('DROP DATABASE IF EXISTS `' . $name . '`');
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function translationFingerprint(string $sql): array
    {
        $stmt = $this->pdo()->query($sql);
        self::assertNotFalse($stmt);

        /** @var list<array<string, mixed>> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(
            static fn (array $row): array => array_map(static fn (mixed $v): mixed => is_int($v) ? (string) $v : $v, $row),
            $rows
        );
    }

    private function scalarInt(string $sql): int
    {
        $stmt = $this->pdo()->query($sql);
        self::assertNotFalse($stmt);
        $value = $stmt->fetchColumn();

        return is_numeric($value) ? (int) $value : 0;
    }
}
