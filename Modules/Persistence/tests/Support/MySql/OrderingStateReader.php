<?php

declare(strict_types=1);

namespace Maatify\Persistence\Tests\Support\MySql;

use PDO;
use PDOStatement;
use PHPUnit\Framework\AssertionFailedError;

final readonly class OrderingStateReader
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @return array<int, int>
     */
    public function globalOrdersById(): array
    {
        $stmt = $this->prepare(
            'SELECT `id`, `display_order`
               FROM `' . OrderingSchemaManager::GLOBAL_TABLE . '`
              ORDER BY `id` ASC'
        );
        $stmt->execute();

        return $this->ordersByIdFromStatement($stmt);
    }

    /**
     * @return array<int, int>
     */
    public function scopedOrdersById(int|string $scopeValue): array
    {
        $stmt = $this->prepare(
            'SELECT `id`, `display_order`
               FROM `' . OrderingSchemaManager::SCOPED_TABLE . '`
              WHERE `scope_key` = :scope_key
              ORDER BY `id` ASC'
        );
        $stmt->execute(['scope_key' => $scopeValue]);

        return $this->ordersByIdFromStatement($stmt);
    }

    /**
     * @param non-empty-string $sql
     */
    private function prepare(string $sql): PDOStatement
    {
        $statement = $this->pdo->prepare($sql);

        if (!$statement instanceof PDOStatement) {
            throw new AssertionFailedError('Unable to prepare ordering state reader statement.');
        }

        return $statement;
    }

    /**
     * @return array<int, int>
     */
    private function ordersByIdFromStatement(PDOStatement $stmt): array
    {
        $ordersById = [];

        while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
            if (!is_array($row)) {
                throw new AssertionFailedError('Ordering state row must be an array.');
            }

            $id = $row['id'] ?? null;
            $displayOrder = $row['display_order'] ?? null;

            if (!is_int($id) && !(is_string($id) && ctype_digit($id))) {
                throw new AssertionFailedError('Ordering state row id must be a positive integer.');
            }

            if (!is_int($displayOrder) && !(is_string($displayOrder) && ctype_digit($displayOrder))) {
                throw new AssertionFailedError('Ordering state display order must be a positive integer.');
            }

            $ordersById[(int) $id] = (int) $displayOrder;
        }

        return $ordersById;
    }
}
