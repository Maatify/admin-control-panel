<?php

declare(strict_types=1);

namespace Maatify\Persistence\Tests\Support\MySql;

use PDO;
use PHPUnit\Framework\TestCase;

abstract class MySqlIntegrationTestCase extends TestCase
{
    protected static PDO $pdo;
    protected static OrderingSchemaManager $schemaManager;

    protected OrderingFixture $fixture;

    public static function setUpBeforeClass(): void
    {
        self::$pdo = (new MySqlConnectionFactory())->create();
        self::$schemaManager = new OrderingSchemaManager(self::$pdo);
        self::$schemaManager->initialize();
    }

    protected function setUp(): void
    {
        $this->rollBackOpenTransaction();
        self::$schemaManager->reset();
        $this->fixture = new OrderingFixture(self::$pdo);
    }

    protected function tearDown(): void
    {
        $this->rollBackOpenTransaction();
        self::$schemaManager->reset();
    }

    public static function tearDownAfterClass(): void
    {
        if (isset(self::$pdo) && self::$pdo->inTransaction()) {
            self::$pdo->rollBack();
        }

        if (isset(self::$schemaManager)) {
            self::$schemaManager->dropTables();
        }
    }

    protected function pdo(): PDO
    {
        return self::$pdo;
    }

    private function rollBackOpenTransaction(): void
    {
        if (self::$pdo->inTransaction()) {
            self::$pdo->rollBack();
        }
    }
}
