<?php

declare(strict_types=1);

namespace Maatify\Persistence\Tests\Support\MySql;

use PDO;
use PDOStatement;
use PHPUnit\Framework\AssertionFailedError;

final readonly class OrderingFixture
{
    public function __construct(private PDO $pdo)
    {
    }

    public function insertGlobal(int $displayOrder, string $label = 'global row', ?string $deletedAt = null): int
    {
        $stmt = $this->prepare(
            'INSERT INTO `' . OrderingSchemaManager::GLOBAL_TABLE . '` (`display_order`, `deleted_at`, `label`)
             VALUES (:display_order, :deleted_at, :label)'
        );
        $stmt->execute([
            'display_order' => $displayOrder,
            'deleted_at'    => $deletedAt,
            'label'         => $label,
        ]);

        return $this->lastInsertId();
    }

    public function insertScoped(int|string $scopeValue, int $displayOrder, string $label = 'scoped row', ?string $deletedAt = null): int
    {
        $stmt = $this->prepare(
            'INSERT INTO `' . OrderingSchemaManager::SCOPED_TABLE . '` (`scope_key`, `display_order`, `deleted_at`, `label`)
             VALUES (:scope_key, :display_order, :deleted_at, :label)'
        );
        $stmt->execute([
            'scope_key'     => $scopeValue,
            'display_order' => $displayOrder,
            'deleted_at'    => $deletedAt,
            'label'         => $label,
        ]);

        return $this->lastInsertId();
    }

    /**
     * @param non-empty-string $sql
     */
    private function prepare(string $sql): PDOStatement
    {
        $statement = $this->pdo->prepare($sql);

        if (!$statement instanceof PDOStatement) {
            throw new AssertionFailedError('Unable to prepare ordering fixture statement.');
        }

        return $statement;
    }

    private function lastInsertId(): int
    {
        $id = $this->pdo->lastInsertId();

        if ($id === false || !ctype_digit($id)) {
            throw new AssertionFailedError('Unable to read inserted ordering fixture id.');
        }

        return (int) $id;
    }
}
