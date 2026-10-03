<?php

declare(strict_types=1);

namespace Tests\Integration\I18n;

use Tests\Support\I18nMigrationSupport;
use Tests\Support\I18nSchemaFingerprint;
use DateTimeZone;
use Maatify\I18n\Consumer\Service\TranslationReadService;
use Maatify\I18n\Management\Command\CreateKeyCommand;
use Maatify\I18n\Management\Command\UpsertTranslationCommand;
use Maatify\I18n\Management\Service\I18nStatsRebuilder;
use Maatify\I18n\Management\Service\TranslationWriteService;
use Maatify\I18n\Repository\Mysql\MysqlDomainLanguageSummaryRepository;
use Maatify\I18n\Repository\Mysql\MysqlDomainRepository;
use Maatify\I18n\Repository\Mysql\MysqlDomainScopeRepository;
use Maatify\I18n\Repository\Mysql\MysqlKeyStatsRepository;
use Maatify\I18n\Repository\Mysql\MysqlScopeRepository;
use Maatify\I18n\Repository\Mysql\MysqlTranslationKeyRepository;
use Maatify\I18n\Repository\Mysql\MysqlTranslationRepository;
use Maatify\I18n\Service\I18nGovernancePolicyService;
use Maatify\I18n\Service\MissingCounterService;
use Maatify\Persistence\Pdo\Transaction\PdoTransactionRunner;
use Maatify\SharedCommon\Infrastructure\SystemClock;
use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;

/**
 * Forward-migration proof: a REAL legacy `i18n_*` (ADR-019 shape) database is
 * migrated with the real migration file to the canonical `maa_i18n_*` set.
 *
 * Proves: no row / identity / value loss (exact codes, NULL scope, binary case,
 * authoritative empty values), relationships kept, derived state preserved and
 * deterministically rebuildable to the same facts, the migrated structure is
 * IDENTICAL to a fresh install of the Package schema, and only the seven
 * canonical tables remain.
 *
 * The legacy shape is the historical fixture
 * tests/admin/Fixtures/i18n_legacy_adr019_schema.sql (migration INPUT only).
 */
final class I18nTablesToMaaNamespaceMigrationIntegrationTest extends TestCase
{
    private const MIGRATION = '/database/migrations/20261001_000001_i18n_tables_to_maa_i18n_namespace.sql';

    /** Applied right after MIGRATION: the Package gained the nullable `type` column (ADR-020). */
    private const TYPE_MIGRATION = '/database/migrations/20261003_000001_maa_i18n_translations_type.sql';
    private const LEGACY_FIXTURE = '/tests/admin/Fixtures/i18n_legacy_adr019_schema.sql';
    private const PACKAGE_SCHEMA = '/vendor/maatify/php-i18n/schema/schema.i18n.sql';

    private const CANONICAL = [
        'maa_i18n_domain_language_summary',
        'maa_i18n_domain_scopes',
        'maa_i18n_domains',
        'maa_i18n_key_stats',
        'maa_i18n_keys',
        'maa_i18n_scopes',
        'maa_i18n_translations',
    ];

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

    // ── the proof ──────────────────────────────────────────────────────────

    public function testMigrationPreservesEveryRowIdentityAndValueAndLeavesOnlyCanonicalTables(): void
    {
        $this->newSchema('i18n_mig_a');
        $this->createLegacyState();
        $before = $this->seedLegacyData();

        $this->runMigration();

        // only the seven canonical tables are the active state
        self::assertSame(self::CANONICAL, $this->tableNames());

        // authoritative state is byte-for-byte preserved (ids, values, timestamps)
        foreach ([
            'maa_i18n_scopes' => 'SELECT id, code, name, description, is_active, sort_order, created_at FROM maa_i18n_scopes ORDER BY id',
            'maa_i18n_domains' => 'SELECT id, code, name, description, is_active, sort_order, created_at FROM maa_i18n_domains ORDER BY id',
            'maa_i18n_domain_scopes' => 'SELECT id, scope_code, domain_code, created_at FROM maa_i18n_domain_scopes ORDER BY id',
            'maa_i18n_keys' => 'SELECT id, scope, domain, key_part, description, created_at FROM maa_i18n_keys ORDER BY id',
            'maa_i18n_translations' => 'SELECT id, key_id, language_code, value, created_at, updated_at FROM maa_i18n_translations ORDER BY id',
        ] as $table => $query) {
            self::assertSame($before[$table], $this->rows($query), $table . ' lost or changed data');
        }

        // exact identities survive: NULL scope, binary case ('ar' <> 'AR'), authoritative ''
        self::assertSame(
            [['ar', 'مرحبا'], ['AR', 'upper'], [null, 'neutral'], ['ar', ''], ['en', 'x']],
            array_map(
                static fn (array $r): array => [$r['language_code'], $r['value']],
                $this->rows('SELECT language_code, value FROM maa_i18n_translations ORDER BY id')
            )
        );
        self::assertSame(
            5,
            $this->scalar('SELECT COUNT(DISTINCT key_id, language_code_identity) FROM maa_i18n_translations'),
            'identity (key_id, exact code) stays unique and distinct'
        );

        // relationships are intact
        self::assertSame(0, $this->scalar('SELECT COUNT(*) FROM maa_i18n_translations t LEFT JOIN maa_i18n_keys k ON k.id = t.key_id WHERE k.id IS NULL'));
        self::assertSame(0, $this->scalar('SELECT COUNT(*) FROM maa_i18n_key_stats s LEFT JOIN maa_i18n_keys k ON k.id = s.key_id WHERE k.id IS NULL'));

        // the cascade FKs still point at the renamed parent
        $fks = $this->rows(
            'SELECT TABLE_NAME, REFERENCED_TABLE_NAME FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL ORDER BY TABLE_NAME'
        );
        self::assertSame([
            ['TABLE_NAME' => 'maa_i18n_key_stats', 'REFERENCED_TABLE_NAME' => 'maa_i18n_keys'],
            ['TABLE_NAME' => 'maa_i18n_translations', 'REFERENCED_TABLE_NAME' => 'maa_i18n_keys'],
        ], $fks);
    }

    public function testDerivedStateIsPreservedAndDeterministicallyRebuildsToTheSameFacts(): void
    {
        $this->newSchema('i18n_mig_b');
        $this->createLegacyState();
        $this->seedLegacyData();

        $statsBefore = $this->rows('SELECT key_id, translated_count FROM i18n_key_stats ORDER BY key_id');
        $summaryBefore = $this->rows('SELECT scope, domain, language_code, total_keys, translated_count, missing_count FROM i18n_domain_language_summary ORDER BY scope, domain, language_code_identity');

        $this->runMigration();

        // preserved
        self::assertSame($statsBefore, $this->rows('SELECT key_id, translated_count FROM maa_i18n_key_stats ORDER BY key_id'));
        self::assertSame($summaryBefore, $this->rows('SELECT scope, domain, language_code, total_keys, translated_count, missing_count FROM maa_i18n_domain_language_summary ORDER BY scope, domain, language_code_identity'));
        // one stats row per key, with its own id
        self::assertSame(
            $this->scalar('SELECT COUNT(*) FROM maa_i18n_keys'),
            $this->scalar('SELECT COUNT(DISTINCT key_id) FROM maa_i18n_key_stats')
        );

        // the Package rebuild proves equivalence from authoritative data
        $pdo = $this->pdo();
        (new I18nStatsRebuilder(
            new PdoTransactionRunner($pdo),
            new MysqlDomainLanguageSummaryRepository($pdo),
            new MysqlKeyStatsRepository($pdo)
        ))->fullRebuild();

        self::assertSame($statsBefore, $this->rows('SELECT key_id, translated_count FROM maa_i18n_key_stats ORDER BY key_id'));
        self::assertSame($summaryBefore, $this->rows('SELECT scope, domain, language_code, total_keys, translated_count, missing_count FROM maa_i18n_domain_language_summary ORDER BY scope, domain, language_code_identity'));
    }

    public function testMigratedStructureIsIdenticalToAFreshInstallOfThePackageSchema(): void
    {
        $this->newSchema('i18n_mig_c');
        $this->createLegacyState();
        $this->seedLegacyData();
        $this->runMigration();
        $migrated = $this->structureFingerprint();

        $this->newSchema('i18n_mig_fresh');
        $this->pdo()->exec($this->file(self::PACKAGE_SCHEMA));
        $fresh = $this->structureFingerprint();

        self::assertSame(
            $fresh,
            $migrated,
            'columns, indexes, constraints, checks, foreign keys, comments of the migrated database must equal a fresh Package install'
        );
        self::assertGreaterThan(40, count($fresh));
    }

    public function testThePackageRuntimeWorksOnTheMigratedDatabase(): void
    {
        $this->newSchema('i18n_mig_d');
        $this->createLegacyState();
        $this->seedLegacyData();
        $this->runMigration();

        $pdo = $this->pdo();
        $clock = new SystemClock(new DateTimeZone('UTC'));
        $keys = new MysqlTranslationKeyRepository($pdo);
        $translations = new MysqlTranslationRepository($pdo, $clock);
        $writer = new TranslationWriteService(
            new PdoTransactionRunner($pdo),
            $keys,
            $translations,
            new I18nGovernancePolicyService(
                new MysqlScopeRepository($pdo),
                new MysqlDomainRepository($pdo),
                new MysqlDomainScopeRepository($pdo)
            ),
            new MissingCounterService(
                new MysqlDomainLanguageSummaryRepository($pdo),
                $keys,
                new MysqlKeyStatsRepository($pdo)
            )
        );
        $reader = new TranslationReadService($keys, $translations);

        // reads of migrated data (exact codes, empty value, NULL scope)
        self::assertSame('مرحبا', $reader->getValue('ar', 'ct', 'home', 'a'));
        self::assertSame('upper', $reader->getValue('AR', 'ct', 'home', 'a'));
        self::assertSame('neutral', $reader->getValue(null, 'ct', 'home', 'a'));
        self::assertSame('', $reader->getValue('ar', 'ct', 'home', 'b'));
        self::assertNull($reader->getValue('fr', 'ct', 'home', 'a'));

        // writes keep the derived state consistent on the migrated tables
        $newKey = $writer->createKey(new CreateKeyCommand('ct', 'home', 'fresh'));
        $writer->upsertTranslation(new UpsertTranslationCommand('ar', $newKey, 'جديد', null));

        self::assertSame(1, $this->scalar('SELECT translated_count FROM maa_i18n_key_stats WHERE key_id = ' . $newKey));
        self::assertSame(3, $this->scalar("SELECT total_keys FROM maa_i18n_domain_language_summary WHERE scope = 'ct' AND domain = 'home' AND language_code = 'ar'"));
    }

    // ── guards ─────────────────────────────────────────────────────────────

    public function testTheMigrationRefusesAPreAdr019DatabaseBeforeRenamingAnything(): void
    {
        $this->newSchema('i18n_mig_e');
        $pdo = $this->pdo();
        $pdo->exec('CREATE TABLE i18n_scopes (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, code VARCHAR(32) NOT NULL) ENGINE=InnoDB');
        // pre-ADR-019 identity column: language_id, no language_code
        $pdo->exec('CREATE TABLE i18n_translations (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, key_id BIGINT UNSIGNED NOT NULL, language_id INT UNSIGNED NOT NULL, value TEXT NOT NULL) ENGINE=InnoDB');

        try {
            $this->runMigration();
            self::fail('The migration must refuse a database that is not in the ADR-019 shape.');
        } catch (PDOException) {
            self::assertTrue(true);
        }

        self::assertSame(['i18n_scopes', 'i18n_translations'], $this->tableNames());
    }

    public function testTheMigrationIsAtomicWhenACanonicalTableAlreadyExists(): void
    {
        $this->newSchema('i18n_mig_f');
        $this->createLegacyState();
        $this->seedLegacyData();
        $this->pdo()->exec('CREATE TABLE maa_i18n_scopes (id INT) ENGINE=InnoDB');

        try {
            $this->runMigration();
            self::fail('The migration must refuse to overwrite an existing canonical table.');
        } catch (PDOException) {
            self::assertTrue(true);
        }

        // all-or-nothing: not a single legacy table was renamed
        self::assertSame(
            ['i18n_domain_language_summary', 'i18n_domain_scopes', 'i18n_domains', 'i18n_key_stats', 'i18n_keys', 'i18n_scopes', 'i18n_translations', 'maa_i18n_scopes'],
            $this->tableNames()
        );
        self::assertSame(2, $this->scalar('SELECT COUNT(*) FROM i18n_scopes'));
    }

    // ── helpers ────────────────────────────────────────────────────────────

    private function pdo(): PDO
    {
        self::assertNotNull(self::$pdo);

        return self::$pdo;
    }

    private function newSchema(string $prefix): string
    {
        $name = I18nMigrationSupport::randomSchemaName($prefix);
        $this->pdo()->exec('CREATE DATABASE `' . $name . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->pdo()->exec('USE `' . $name . '`');
        $this->schemas[] = $name;

        return $name;
    }

    private function file(string $relative): string
    {
        $sql = file_get_contents(dirname(__DIR__, 4) . $relative);
        self::assertIsString($sql);

        return $sql;
    }

    private function createLegacyState(): void
    {
        $this->pdo()->exec($this->file(self::LEGACY_FIXTURE));
    }

    /**
     * Seeds a rich legacy state and returns the authoritative fingerprints
     * (keyed by the canonical table that must contain them afterwards).
     *
     * @return array<string, list<array<string, mixed>>>
     */
    private function seedLegacyData(): array
    {
        $pdo = $this->pdo();

        $pdo->exec("INSERT INTO i18n_scopes (code, name, description, is_active, sort_order) VALUES ('ct', 'Website', 'public site', 1, 1), ('ad', 'Admin', NULL, 0, 2)");
        $pdo->exec("INSERT INTO i18n_domains (code, name, description, sort_order) VALUES ('home', 'Home', NULL, 1), ('cart', 'Cart', 'checkout', 2)");
        $pdo->exec("INSERT INTO i18n_domain_scopes (scope_code, domain_code) VALUES ('ct', 'home'), ('ct', 'cart'), ('ad', 'home')");
        $pdo->exec("INSERT INTO i18n_keys (scope, domain, key_part, description) VALUES ('ct', 'home', 'a', 'first'), ('ct', 'home', 'b', NULL), ('ct', 'cart', 'c', NULL)");
        $pdo->exec(
            "INSERT INTO i18n_translations (key_id, language_code, value) VALUES
                (1, 'ar', 'مرحبا'), (1, 'AR', 'upper'), (1, NULL, 'neutral'), (2, 'ar', ''), (3, 'en', 'x')"
        );
        // derived layers exactly as the legacy runtime maintains them
        $pdo->exec('INSERT INTO i18n_key_stats (key_id, translated_count) VALUES (1, 3), (2, 1), (3, 1)');
        $pdo->exec(
            "INSERT INTO i18n_domain_language_summary (scope, domain, language_code, total_keys, translated_count, missing_count) VALUES
                ('ct', 'home', 'ar', 2, 2, 0), ('ct', 'home', 'AR', 2, 1, 1), ('ct', 'home', NULL, 2, 1, 1), ('ct', 'cart', 'en', 1, 1, 0)"
        );

        return [
            'maa_i18n_scopes' => $this->rows('SELECT id, code, name, description, is_active, sort_order, created_at FROM i18n_scopes ORDER BY id'),
            'maa_i18n_domains' => $this->rows('SELECT id, code, name, description, is_active, sort_order, created_at FROM i18n_domains ORDER BY id'),
            'maa_i18n_domain_scopes' => $this->rows('SELECT id, scope_code, domain_code, created_at FROM i18n_domain_scopes ORDER BY id'),
            'maa_i18n_keys' => $this->rows('SELECT id, scope, domain, key_part, description, created_at FROM i18n_keys ORDER BY id'),
            'maa_i18n_translations' => $this->rows('SELECT id, key_id, language_code, value, created_at, updated_at FROM i18n_translations ORDER BY id'),
        ];
    }

    /**
     * Runs the real migration files (namespace, then translation type) statement by statement (stops on the first
     * error, exactly like the mysql CLI does).
     */
    private function runMigration(): void
    {
        foreach ([self::MIGRATION, self::TYPE_MIGRATION] as $migrationFile) {
            $lines = array_filter(
                explode("\n", $this->file($migrationFile)),
                static fn (string $line): bool => !str_starts_with(ltrim($line), '--')
            );

            foreach (explode(";\n", implode("\n", $lines) . "\n") as $statement) {
                $statement = trim($statement);
                if ($statement !== '') {
                    $this->pdo()->exec($statement);
                }
            }
        }
    }

    /**
     * @return list<string>
     */
    private function tableNames(): array
    {
        $stmt = $this->pdo()->query('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME');
        self::assertNotFalse($stmt);

        $names = [];
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $name) {
            $names[] = is_string($name) ? $name : '';
        }

        // PHP byte order: independent of the server collation ('_' sorts differently per engine)
        sort($names, SORT_STRING);

        return $names;
    }

    /**
     * @return list<string>
     */
    private function structureFingerprint(): array
    {
        return I18nSchemaFingerprint::of($this->pdo());
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function rows(string $sql): array
    {
        $stmt = $this->pdo()->query($sql);
        self::assertNotFalse($stmt);

        /** @var list<array<string, mixed>> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $rows;
    }

    private function scalar(string $sql): int
    {
        $stmt = $this->pdo()->query($sql);
        self::assertNotFalse($stmt);
        $value = $stmt->fetchColumn();

        return is_numeric($value) ? (int) $value : 0;
    }
}
