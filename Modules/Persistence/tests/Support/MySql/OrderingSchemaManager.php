<?php

declare(strict_types=1);

namespace Maatify\Persistence\Tests\Support\MySql;

use PDO;
use PHPUnit\Framework\AssertionFailedError;

final readonly class OrderingSchemaManager
{
    public const GLOBAL_TABLE = 'maa_persistence_test_global_ordering';
    public const SCOPED_TABLE = 'maa_persistence_test_scoped_ordering';

    public function __construct(private PDO $pdo)
    {
    }

    public function initialize(): void
    {
        $this->dropTables();
        $this->executeFixture('create_global_ordering_table.sql');
        $this->executeFixture('create_scoped_ordering_table.sql');
    }

    public function reset(): void
    {
        $this->pdo->exec('TRUNCATE TABLE `' . self::SCOPED_TABLE . '`');
        $this->pdo->exec('TRUNCATE TABLE `' . self::GLOBAL_TABLE . '`');
    }

    public function dropTables(): void
    {
        $this->pdo->exec('DROP TABLE IF EXISTS `' . self::SCOPED_TABLE . '`');
        $this->pdo->exec('DROP TABLE IF EXISTS `' . self::GLOBAL_TABLE . '`');
    }

    /**
     * @param non-empty-string $filename
     */
    private function executeFixture(string $filename): void
    {
        $path = dirname(__DIR__, 2) . '/Fixtures/MySql/' . $filename;
        $sql = file_get_contents($path);

        if (!is_string($sql) || $sql === '') {
            throw new AssertionFailedError(sprintf('Unable to read MySQL fixture %s.', $filename));
        }

        $this->pdo->exec($sql);
    }
}
