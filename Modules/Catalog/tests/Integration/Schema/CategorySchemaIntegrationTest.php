<?php

declare(strict_types=1);

namespace Maatify\Catalog\Tests\Integration\Schema;

use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class CategorySchemaIntegrationTest extends TestCase
{
    private const CATEGORY_TABLE = 'maa_catalog_categories';
    private const TRANSLATION_TABLE = 'maa_catalog_category_translations';
    private const INSERT_TRIGGER = 'trg_maa_catalog_categories_parent_not_self_ai';
    private const UPDATE_TRIGGER = 'trg_maa_catalog_categories_parent_not_self_bu';

    private static ?PDO $connection = null;

    public static function setUpBeforeClass(): void
    {
        $dsn = self::requiredEnvironmentVariable('CATALOG_TEST_DSN');
        $username = self::requiredEnvironmentVariable('CATALOG_TEST_DB_USER');
        $password = self::requiredEnvironmentVariable('CATALOG_TEST_DB_PASSWORD');

        try {
            self::$connection = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $exception) {
            throw new RuntimeException('Catalog MySQL integration connection failed.', 0, $exception);
        }
    }

    public static function tearDownAfterClass(): void
    {
        self::$connection = null;
    }

    protected function setUp(): void
    {
        $this->dropSchema();
        $this->installSchema();
    }

    protected function tearDown(): void
    {
        $this->dropSchema();
        self::assertSame([], $this->tableNames());
        self::assertSame([], $this->triggerNames());
    }

    public function testSchemaCanBeInstalledAndCleanedUpRepeatedly(): void
    {
        self::assertSame([
            self::CATEGORY_TABLE,
            self::TRANSLATION_TABLE,
        ], $this->tableNames());
        self::assertSame([
            self::INSERT_TRIGGER,
            self::UPDATE_TRIGGER,
        ], $this->triggerNames());
        $this->assertTrigger(self::INSERT_TRIGGER, 'AFTER', 'INSERT');
        $this->assertTrigger(self::UPDATE_TRIGGER, 'BEFORE', 'UPDATE');
        $this->assertTableStorage(self::CATEGORY_TABLE);
        $this->assertTableStorage(self::TRANSLATION_TABLE);

        $this->dropSchema();
        self::assertSame([], $this->tableNames());
        self::assertSame([], $this->triggerNames());

        $this->installSchema();
        self::assertSame([
            self::CATEGORY_TABLE,
            self::TRANSLATION_TABLE,
        ], $this->tableNames());
        self::assertSame([
            self::INSERT_TRIGGER,
            self::UPDATE_TRIGGER,
        ], $this->triggerNames());
        $this->assertTableStorage(self::CATEGORY_TABLE);
        $this->assertTableStorage(self::TRANSLATION_TABLE);
    }

    public function testValidCategoryHierarchyAndTranslationCanBeStored(): void
    {
        $this->insertCategory(1, null, 'clothing', 'active');
        $this->insertCategory(2, 1, 'shirts', 'inactive');
        $this->insertTranslation(1, 2, 'en-US');

        self::assertSame(2, $this->rowCount(self::CATEGORY_TABLE));
        self::assertSame(1, $this->rowCount(self::TRANSLATION_TABLE));
    }

    public function testCategoryCodeMustBeUnique(): void
    {
        $this->insertCategory(1, null, 'clothing', 'active');

        $this->expectException(PDOException::class);
        $this->insertCategory(2, null, 'clothing', 'active');
    }

    public function testTranslationIdentityMustBeUnique(): void
    {
        $this->insertCategory(1, null, 'clothing', 'active');
        $this->insertTranslation(1, 1, 'en-US');

        $this->expectException(PDOException::class);
        $this->insertTranslation(2, 1, 'en-US');
    }

    public function testStatusCheckRejectsUnknownValues(): void
    {
        $this->expectException(PDOException::class);
        $this->insertCategory(1, null, 'clothing', 'archived');
    }

    public function testSelfParentIsRejectedOnInsert(): void
    {
        $this->expectException(PDOException::class);
        $this->insertCategory(1, 1, 'clothing', 'active');
    }

    public function testSelfParentIsRejectedOnUpdate(): void
    {
        $this->insertCategory(1, null, 'clothing', 'active');

        $this->expectException(PDOException::class);
        $this->connection()->exec(
            'UPDATE `' . self::CATEGORY_TABLE . '` SET `parent_id` = `id` WHERE `id` = 1',
        );
    }

    public function testTranslationForeignKeyRejectsMissingCategory(): void
    {
        $this->expectException(PDOException::class);
        $this->insertTranslation(1, 999, 'en-US');
    }

    public function testParentAndCategoryCannotBeDeletedWhileDependentsExist(): void
    {
        $this->insertCategory(1, null, 'clothing', 'active');
        $this->insertCategory(2, 1, 'shirts', 'active');
        $this->insertTranslation(1, 1, 'en-US');

        $this->expectException(PDOException::class);
        $this->connection()->exec('DELETE FROM `' . self::CATEGORY_TABLE . '` WHERE `id` = 1');
    }

    public function testCategoryIdCannotBeUpdatedWhileChildReferencesIt(): void
    {
        $this->insertCategory(1, null, 'clothing', 'active');
        $this->insertCategory(2, 1, 'shirts', 'active');

        $this->expectException(PDOException::class);
        $this->connection()->exec('UPDATE `' . self::CATEGORY_TABLE . '` SET `id` = 10 WHERE `id` = 1');
    }

    private static function requiredEnvironmentVariable(string $name): string
    {
        $value = getenv($name);

        if (!is_string($value) || trim($value) === '') {
            throw new RuntimeException(sprintf('%s must be configured for Catalog integration tests.', $name));
        }

        return $value;
    }

    private function installSchema(): void
    {
        $schemaPath = dirname(__DIR__, 3) . '/schema/catalog.sql';
        $schema = file_get_contents($schemaPath);

        if ($schema === false) {
            throw new RuntimeException('Unable to read the canonical Catalog schema.');
        }

        $schema = preg_replace(
            [
                '/^[ \t]*--[^\r\n]*(?:\r\n|\n|$)/m',
                '/^[ \t]*DELIMITER[ \t]+\S+[ \t]*$/mi',
            ],
            '',
            $schema,
        );

        if ($schema === null) {
            throw new RuntimeException('Unable to normalize the canonical Catalog schema.');
        }

        $statements = preg_split(
            '/;\s*(?=CREATE\s+(?:TABLE|TRIGGER)\b)/i',
            str_replace('$$', ';', trim($schema)),
            -1,
            PREG_SPLIT_NO_EMPTY,
        );

        if (!is_array($statements) || count($statements) !== 4) {
            throw new RuntimeException('The canonical Catalog schema must contain exactly two tables and two triggers.');
        }

        $tableStatements = 0;
        $triggerStatements = 0;

        foreach ($statements as $statement) {
            if (trim($statement) === '') {
                throw new RuntimeException('The canonical Catalog schema contains an empty statement.');
            }

            if (preg_match('/(?:^|\n)[ \t]*CREATE\s+TABLE\b/i', $statement) === 1) {
                $tableStatements++;
            } elseif (preg_match('/(?:^|\n)[ \t]*CREATE\s+TRIGGER\b/i', $statement) === 1) {
                $triggerStatements++;
            } else {
                throw new RuntimeException('The canonical Catalog schema contains an unexpected statement.');
            }

            $this->connection()->exec($statement);
        }

        if ($tableStatements !== 2 || $triggerStatements !== 2) {
            throw new RuntimeException('The canonical Catalog schema must contain exactly two tables and two triggers.');
        }
    }

    private function dropSchema(): void
    {
        $connection = $this->connection();
        $connection->exec('DROP TRIGGER IF EXISTS `' . self::INSERT_TRIGGER . '`');
        $connection->exec('DROP TRIGGER IF EXISTS `' . self::UPDATE_TRIGGER . '`');
        $connection->exec('DROP TABLE IF EXISTS `' . self::TRANSLATION_TABLE . '`');
        $connection->exec('DROP TABLE IF EXISTS `' . self::CATEGORY_TABLE . '`');
    }

    private function insertCategory(int $id, ?int $parentId, string $code, string $status): void
    {
        $statement = $this->connection()->prepare(
            'INSERT INTO `' . self::CATEGORY_TABLE . '` '
            . '(`id`, `parent_id`, `code`, `status`, `display_order`, `created_at`, `updated_at`, `deleted_at`) '
            . 'VALUES (:id, :parent_id, :code, :status, :display_order, :created_at, :updated_at, :deleted_at)',
        );
        $statement->execute([
            'id' => $id,
            'parent_id' => $parentId,
            'code' => $code,
            'status' => $status,
            'display_order' => 0,
            'created_at' => '2026-01-01 00:00:00',
            'updated_at' => '2026-01-01 00:00:00',
            'deleted_at' => null,
        ]);
    }

    private function insertTranslation(int $id, int $categoryId, string $languageCode): void
    {
        $statement = $this->connection()->prepare(
            'INSERT INTO `' . self::TRANSLATION_TABLE . '` '
            . '(`id`, `category_id`, `language_code`, `name`, `description`, `created_at`, `updated_at`, `deleted_at`) '
            . 'VALUES (:id, :category_id, :language_code, :name, :description, :created_at, :updated_at, :deleted_at)',
        );
        $statement->execute([
            'id' => $id,
            'category_id' => $categoryId,
            'language_code' => $languageCode,
            'name' => 'Clothing',
            'description' => null,
            'created_at' => '2026-01-01 00:00:00',
            'updated_at' => '2026-01-01 00:00:00',
            'deleted_at' => null,
        ]);
    }

    /** @return list<string> */
    private function tableNames(): array
    {
        $statement = $this->connection()->query(
            'SELECT TABLE_NAME FROM information_schema.TABLES '
            . 'WHERE TABLE_SCHEMA = DATABASE() '
            . 'AND TABLE_NAME IN ('
            . "'" . self::CATEGORY_TABLE . "', '" . self::TRANSLATION_TABLE . "')"
            . ' ORDER BY TABLE_NAME',
        );

        if ($statement === false) {
            throw new RuntimeException('Unable to inspect the Catalog schema tables.');
        }

        /** @var list<array<string, mixed>> $rows */
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        $names = [];

        foreach ($rows as $row) {
            if (!isset($row['TABLE_NAME']) || !is_string($row['TABLE_NAME'])) {
                throw new RuntimeException('Catalog table metadata is incomplete.');
            }

            $names[] = $row['TABLE_NAME'];
        }

        return $names;
    }

    /** @return list<string> */
    private function triggerNames(): array
    {
        $statement = $this->connection()->query(
            'SELECT TRIGGER_NAME FROM information_schema.TRIGGERS '
            . 'WHERE TRIGGER_SCHEMA = DATABASE() '
            . 'AND TRIGGER_NAME IN ('
            . "'" . self::INSERT_TRIGGER . "', '" . self::UPDATE_TRIGGER . "')"
            . ' ORDER BY TRIGGER_NAME',
        );

        if ($statement === false) {
            throw new RuntimeException('Unable to inspect the Catalog schema triggers.');
        }

        /** @var list<array<string, mixed>> $rows */
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        $names = [];

        foreach ($rows as $row) {
            if (!isset($row['TRIGGER_NAME']) || !is_string($row['TRIGGER_NAME'])) {
                throw new RuntimeException('Catalog trigger metadata is incomplete.');
            }

            $names[] = $row['TRIGGER_NAME'];
        }

        return $names;
    }

    private function rowCount(string $table): int
    {
        $statement = $this->connection()->query('SELECT COUNT(*) FROM `' . $table . '`');

        if ($statement === false) {
            throw new RuntimeException(sprintf('Unable to count rows in %s.', $table));
        }

        return (int) $statement->fetchColumn();
    }

    private function assertTableStorage(string $table): void
    {
        $statement = $this->connection()->prepare(
            'SELECT ENGINE, TABLE_COLLATION FROM information_schema.TABLES '
            . 'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table',
        );
        $statement->execute(['table' => $table]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if (!is_array($row)) {
            throw new RuntimeException(sprintf('Table %s was not created.', $table));
        }

        if (!isset($row['ENGINE'], $row['TABLE_COLLATION'])
            || !is_string($row['ENGINE'])
            || !is_string($row['TABLE_COLLATION'])) {
            throw new RuntimeException(sprintf('Storage metadata for %s is incomplete.', $table));
        }

        self::assertSame('InnoDB', $row['ENGINE']);
        self::assertSame('utf8mb4_unicode_ci', $row['TABLE_COLLATION']);
    }

    private function assertTrigger(string $name, string $timing, string $event): void
    {
        $statement = $this->connection()->prepare(
            'SELECT TRIGGER_NAME, ACTION_TIMING, EVENT_MANIPULATION, EVENT_OBJECT_TABLE '
            . 'FROM information_schema.TRIGGERS '
            . 'WHERE TRIGGER_SCHEMA = DATABASE() AND TRIGGER_NAME = :trigger',
        );
        $statement->execute(['trigger' => $name]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if (!is_array($row)) {
            throw new RuntimeException(sprintf('Trigger %s was not created.', $name));
        }

        foreach (['TRIGGER_NAME', 'ACTION_TIMING', 'EVENT_MANIPULATION', 'EVENT_OBJECT_TABLE'] as $column) {
            if (!isset($row[$column]) || !is_string($row[$column])) {
                throw new RuntimeException(sprintf('Trigger metadata for %s is incomplete.', $name));
            }
        }

        self::assertSame($name, $row['TRIGGER_NAME']);
        self::assertSame($timing, $row['ACTION_TIMING']);
        self::assertSame($event, $row['EVENT_MANIPULATION']);
        self::assertSame(self::CATEGORY_TABLE, $row['EVENT_OBJECT_TABLE']);
    }

    private function connection(): PDO
    {
        if (self::$connection === null) {
            throw new RuntimeException('Catalog MySQL integration connection is not initialized.');
        }

        return self::$connection;
    }
}
