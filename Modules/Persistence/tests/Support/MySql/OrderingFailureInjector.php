<?php

declare(strict_types=1);

namespace Maatify\Persistence\Tests\Support\MySql;

use PDO;
use PHPUnit\Framework\AssertionFailedError;

final readonly class OrderingFailureInjector
{
    public const GLOBAL_TRIGGER = 'maa_persistence_test_fail_global_target_update';
    public const SCOPED_TRIGGER = 'maa_persistence_test_fail_scoped_target_update';

    private const FAILURE_MESSAGE = 'Forced ordering target update failure.';

    public function __construct(private PDO $pdo)
    {
    }

    public function createGlobalTargetUpdateFailure(int $targetId): void
    {
        $this->createTargetUpdateFailure(
            self::GLOBAL_TRIGGER,
            OrderingSchemaManager::GLOBAL_TABLE,
            $targetId
        );
    }

    public function createScopedTargetUpdateFailure(int $targetId): void
    {
        $this->createTargetUpdateFailure(
            self::SCOPED_TRIGGER,
            OrderingSchemaManager::SCOPED_TABLE,
            $targetId
        );
    }

    public function dropAll(): void
    {
        $this->dropTrigger(self::SCOPED_TRIGGER);
        $this->dropTrigger(self::GLOBAL_TRIGGER);
    }

    /**
     * @param non-empty-string $triggerName
     * @param non-empty-string $tableName
     */
    private function createTargetUpdateFailure(string $triggerName, string $tableName, int $targetId): void
    {
        if ($targetId <= 0) {
            throw new AssertionFailedError('Failure injector target id must be a positive integer.');
        }

        $this->dropTrigger($triggerName);

        $sql = 'CREATE TRIGGER `' . $triggerName . '`'
            . ' BEFORE UPDATE ON `' . $tableName . '`'
            . ' FOR EACH ROW'
            . ' BEGIN'
            . ' IF NEW.`id` = ' . $targetId . ' THEN'
            . " SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = '" . self::FAILURE_MESSAGE . "';"
            . ' END IF;'
            . ' END';

        $this->exec($sql, 'Unable to create ordering failure trigger.');
    }

    /**
     * @param non-empty-string $triggerName
     */
    private function dropTrigger(string $triggerName): void
    {
        $this->exec('DROP TRIGGER IF EXISTS `' . $triggerName . '`', 'Unable to drop ordering failure trigger.');
    }

    /**
     * @param non-empty-string $sql
     * @param non-empty-string $failureMessage
     */
    private function exec(string $sql, string $failureMessage): void
    {
        $result = $this->pdo->exec($sql);

        if ($result === false) {
            throw new AssertionFailedError($failureMessage);
        }
    }
}
