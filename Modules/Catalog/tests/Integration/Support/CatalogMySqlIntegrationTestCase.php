<?php

declare(strict_types=1);

namespace Maatify\Catalog\Tests\Integration\Support;

use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

abstract class CatalogMySqlIntegrationTestCase extends TestCase
{
    private const CATEGORY_TABLE = 'maa_catalog_categories';
    private const TRANSLATION_TABLE = 'maa_catalog_category_translations';
    private const INSERT_TRIGGER = 'trg_maa_catalog_categories_parent_not_self_ai';
    private const UPDATE_TRIGGER = 'trg_maa_catalog_categories_parent_not_self_bu';

    private static ?PDO $connection = null;

    public static function setUpBeforeClass(): void
    {
        self::$connection = self::createConnection();
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
    }

    protected function connection(): PDO
    {
        if (self::$connection === null) {
            throw new RuntimeException('Catalog MySQL integration connection is not initialized.');
        }

        return self::$connection;
    }

    protected function newConnection(): PDO
    {
        return self::createConnection();
    }

    private static function createConnection(): PDO
    {
        $dsn = self::requiredEnvironmentVariable('CATALOG_TEST_DSN');
        $username = self::requiredEnvironmentVariable('CATALOG_TEST_DB_USER');
        $password = self::requiredEnvironmentVariable('CATALOG_TEST_DB_PASSWORD');

        try {
            return new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $exception) {
            throw new RuntimeException('Catalog MySQL integration connection failed.', 0, $exception);
        }
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
            throw new RuntimeException('The canonical Catalog schema must contain two tables and two triggers.');
        }

        foreach ($statements as $statement) {
            $this->connection()->exec($statement);
        }
    }

    private function dropSchema(): void
    {
        $connection = $this->connection();
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        $connection->exec('DROP TRIGGER IF EXISTS `' . self::INSERT_TRIGGER . '`');
        $connection->exec('DROP TRIGGER IF EXISTS `' . self::UPDATE_TRIGGER . '`');
        $connection->exec('DROP TABLE IF EXISTS `' . self::TRANSLATION_TABLE . '`');
        $connection->exec('DROP TABLE IF EXISTS `' . self::CATEGORY_TABLE . '`');
    }
}
