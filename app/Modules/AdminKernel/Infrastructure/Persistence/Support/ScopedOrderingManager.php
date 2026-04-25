<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Persistence\Support;

use PDO;
use PDOException;
use Throwable;

final readonly class ScopedOrderingManager
{
    public function __construct(
        private PDO $pdo,
    ) {
    }

    /**
     * Appends a new item to the end of the current scope.
     *
     * Use this when you create a new row and want it to appear after all
     * existing siblings in the same family/scope.
     *
     * Returns the next display order value that should be assigned.
     *
     * Example:
     * - Scope: product_id = 10
     * - Existing orders: 1, 2, 3
     * - Result: 4
     *
     * @param array<string, int|string|bool|null> $scope
     */
    public function getNextPosition(
        string $table,
        string $orderColumn = 'display_order',
        array $scope = []
    ): int {
        $this->assertIdentifier($table);
        $this->assertIdentifier($orderColumn);
        $this->assertScopeIdentifiers($scope);

        $sql = sprintf(
            'SELECT COALESCE(MAX(%s), 0) AS max_order FROM %s%s',
            $this->quoteIdentifier($orderColumn),
            $this->quoteIdentifier($table),
            $this->buildWhereClause($scope)
        );

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->buildScopeParams($scope));

        $maxOrder = (int)($stmt->fetchColumn() ?: 0);

        return $maxOrder + 1;
    }

    /**
     * Inserts a new item at a specific position within the same scope.
     *
     * This method shifts existing siblings down (+1) starting from the target
     * position, then returns the final valid position that should be written
     * into the new row by the caller.
     *
     * This method does NOT insert the row itself.
     * The caller is expected to:
     * 1) begin transaction
     * 2) call this method
     * 3) insert the new row using the returned position
     * 4) commit transaction
     *
     * @param array<string, int|string|bool|null> $scope
     */
    public function reservePositionForInsert(
        string $table,
        int $requestedPosition,
        string $orderColumn = 'display_order',
        array $scope = []
    ): int {
        $this->assertIdentifier($table);
        $this->assertIdentifier($orderColumn);
        $this->assertScopeIdentifiers($scope);

        $maxPosition = $this->getMaxPosition($table, $orderColumn, $scope);
        $targetPosition = $this->clampInsertPosition($requestedPosition, $maxPosition);

        $sql = sprintf(
            'UPDATE %s
             SET %s = %s + 1
             WHERE %s >= :target_position%s',
            $this->quoteIdentifier($table),
            $this->quoteIdentifier($orderColumn),
            $this->quoteIdentifier($orderColumn),
            $this->quoteIdentifier($orderColumn),
            $this->buildAndScopeClause($scope)
        );

        $params = array_merge(
            ['target_position' => $targetPosition],
            $this->buildScopeParams($scope)
        );

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $targetPosition;
    }

    /**
     * Moves an existing item to a new position inside the SAME scope/family.
     *
     * Behavior:
     * - If moving upward, siblings in the affected range are shifted down (+1)
     * - If moving downward, siblings in the affected range are shifted up (-1)
     * - The target position is clamped into a valid range
     *
     * Returns true if the item order is now correct.
     *
     * Notes:
     * - This method only handles reordering within the same scope.
     * - It expects the item to already belong to that scope.
     *
     * @param array<string, int|string|bool|null> $scope
     */
    public function moveWithinScope(
        string $table,
        string $idColumn,
        int|string $idValue,
        int $currentPosition,
        int $requestedPosition,
        string $orderColumn = 'display_order',
        array $scope = []
    ): bool {
        $this->assertIdentifier($table);
        $this->assertIdentifier($idColumn);
        $this->assertIdentifier($orderColumn);
        $this->assertScopeIdentifiers($scope);

        $maxPosition = $this->getMaxPosition($table, $orderColumn, $scope);
        if ($maxPosition <= 0) {
            return false;
        }

        $targetPosition = $this->clampMovePosition($requestedPosition, $maxPosition);

        if ($targetPosition === $currentPosition) {
            return true;
        }

        $this->pdo->beginTransaction();

        try {
            if ($targetPosition < $currentPosition) {
                $sql = sprintf(
                    'UPDATE %s
                     SET %s = %s + 1
                     WHERE %s >= :target_position
                       AND %s < :current_position
                       AND %s <> :current_id%s',
                    $this->quoteIdentifier($table),
                    $this->quoteIdentifier($orderColumn),
                    $this->quoteIdentifier($orderColumn),
                    $this->quoteIdentifier($orderColumn),
                    $this->quoteIdentifier($orderColumn),
                    $this->quoteIdentifier($idColumn),
                    $this->buildAndScopeClause($scope)
                );

                $params = array_merge(
                    [
                        'target_position' => $targetPosition,
                        'current_position' => $currentPosition,
                        'current_id' => $idValue,
                    ],
                    $this->buildScopeParams($scope)
                );

                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
            } else {
                $sql = sprintf(
                    'UPDATE %s
                     SET %s = %s - 1
                     WHERE %s <= :target_position
                       AND %s > :current_position
                       AND %s <> :current_id%s',
                    $this->quoteIdentifier($table),
                    $this->quoteIdentifier($orderColumn),
                    $this->quoteIdentifier($orderColumn),
                    $this->quoteIdentifier($orderColumn),
                    $this->quoteIdentifier($orderColumn),
                    $this->quoteIdentifier($idColumn),
                    $this->buildAndScopeClause($scope)
                );

                $params = array_merge(
                    [
                        'target_position' => $targetPosition,
                        'current_position' => $currentPosition,
                        'current_id' => $idValue,
                    ],
                    $this->buildScopeParams($scope)
                );

                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
            }

            $sql = sprintf(
                'UPDATE %s
                 SET %s = :target_position
                 WHERE %s = :current_id',
                $this->quoteIdentifier($table),
                $this->quoteIdentifier($orderColumn),
                $this->quoteIdentifier($idColumn)
            );

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'target_position' => $targetPosition,
                'current_id' => $idValue,
            ]);

            $this->pdo->commit();

            return true;
        } catch (Throwable $e) {
            $this->rollbackIfNeeded();
            throw $e;
        }
    }

    /**
     * Moves an item from one scope/family to another.
     *
     * Typical example:
     * - Item was under product_id = 10 at position 3
     * - Item is moved to product_id = 25 at requested position 2
     *
     * This method:
     * 1) Compacts the old scope after removing the item
     * 2) Reserves a valid position inside the new scope
     * 3) Updates the item's scope columns and order column
     *
     * Returns the final position used in the new scope.
     *
     * IMPORTANT:
     * - oldScope must represent the current family of the row
     * - newScope must represent the target family of the row
     *
     * @param array<string, int|string|bool|null> $oldScope
     * @param array<string, int|string|bool|null> $newScope
     */
    public function moveToAnotherScope(
        string $table,
        string $idColumn,
        int|string $idValue,
        int $currentPosition,
        int $requestedPosition,
        array $oldScope,
        array $newScope,
        string $orderColumn = 'display_order'
    ): int {
        $this->assertIdentifier($table);
        $this->assertIdentifier($idColumn);
        $this->assertIdentifier($orderColumn);
        $this->assertScopeIdentifiers($oldScope);
        $this->assertScopeIdentifiers($newScope);

        $this->pdo->beginTransaction();

        try {
            $this->compactScopeAfterRemoval(
                table: $table,
                removedPosition: $currentPosition,
                orderColumn: $orderColumn,
                scope: $oldScope
            );

            $targetPosition = $this->reservePositionForInsert(
                table: $table,
                requestedPosition: $requestedPosition,
                orderColumn: $orderColumn,
                scope: $newScope
            );

            $setParts = [];
            $params = [
                'target_position' => $targetPosition,
                'current_id' => $idValue,
            ];

            foreach ($newScope as $column => $value) {
                $paramKey = 'scope_set_' . $column;
                $setParts[] = sprintf(
                    '%s = :%s',
                    $this->quoteIdentifier($column),
                    $paramKey
                );
                $params[$paramKey] = $value;
            }

            $setParts[] = sprintf(
                '%s = :target_position',
                $this->quoteIdentifier($orderColumn)
            );

            $sql = sprintf(
                'UPDATE %s
                 SET %s
                 WHERE %s = :current_id',
                $this->quoteIdentifier($table),
                implode(', ', $setParts),
                $this->quoteIdentifier($idColumn)
            );

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            $this->pdo->commit();

            return $targetPosition;
        } catch (Throwable $e) {
            $this->rollbackIfNeeded();
            throw $e;
        }
    }

    /**
     * Compacts a scope after an item has been removed.
     *
     * Example:
     * - Existing order: 1, 2, 3, 4, 5
     * - Removed item at position: 3
     * - Resulting order becomes: 1, 2, 3, 4
     *
     * This method is usually called after DELETE,
     * but it can also be called after moving an item out of the scope.
     *
     * @param array<string, int|string|bool|null> $scope
     */
    public function compactScopeAfterRemoval(
        string $table,
        int $removedPosition,
        string $orderColumn = 'display_order',
        array $scope = []
    ): void {
        $this->assertIdentifier($table);
        $this->assertIdentifier($orderColumn);
        $this->assertScopeIdentifiers($scope);

        $sql = sprintf(
            'UPDATE %s
             SET %s = %s - 1
             WHERE %s > :removed_position%s',
            $this->quoteIdentifier($table),
            $this->quoteIdentifier($orderColumn),
            $this->quoteIdentifier($orderColumn),
            $this->quoteIdentifier($orderColumn),
            $this->buildAndScopeClause($scope)
        );

        $params = array_merge(
            ['removed_position' => $removedPosition],
            $this->buildScopeParams($scope)
        );

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }

    /**
     * Rebuilds the order sequence for the whole scope from 1..N.
     *
     * Use this as a recovery/maintenance operation when the scope may contain:
     * - duplicated positions
     * - missing gaps
     * - inconsistent ordering caused by legacy/manual updates
     *
     * Returns the number of rows that were normalized.
     *
     * IMPORTANT:
     * - This method uses row-by-row updates inside a transaction
     * - It should not be your first choice for normal reorder operations
     * - It is best used as a corrective operation
     *
     * @param array<string, int|string|bool|null> $scope
     */
    public function normalizeScope(
        string $table,
        string $idColumn,
        string $orderColumn = 'display_order',
        array $scope = []
    ): int {
        $this->assertIdentifier($table);
        $this->assertIdentifier($idColumn);
        $this->assertIdentifier($orderColumn);
        $this->assertScopeIdentifiers($scope);

        $sql = sprintf(
            'SELECT %s
             FROM %s%s
             ORDER BY %s ASC, %s ASC',
            $this->quoteIdentifier($idColumn),
            $this->quoteIdentifier($table),
            $this->buildWhereClause($scope),
            $this->quoteIdentifier($orderColumn),
            $this->quoteIdentifier($idColumn)
        );

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->buildScopeParams($scope));

        /** @var list<int|string> $ids */
        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if ($ids === []) {
            return 0;
        }

        $this->pdo->beginTransaction();

        try {
            $position = 1;

            foreach ($ids as $id) {
                $updateSql = sprintf(
                    'UPDATE %s
                     SET %s = :position
                     WHERE %s = :current_id',
                    $this->quoteIdentifier($table),
                    $this->quoteIdentifier($orderColumn),
                    $this->quoteIdentifier($idColumn)
                );

                $updateStmt = $this->pdo->prepare($updateSql);
                $updateStmt->execute([
                    'position' => $position,
                    'current_id' => $id,
                ]);

                $position++;
            }

            $this->pdo->commit();

            return count($ids);
        } catch (Throwable $e) {
            $this->rollbackIfNeeded();
            throw $e;
        }
    }

    /**
     * Returns the current maximum position inside the given scope.
     *
     * Example:
     * - If the scope contains 5 items ordered 1..5, result is 5
     * - If the scope is empty, result is 0
     *
     * @param array<string, int|string|bool|null> $scope
     */
    public function getMaxPosition(
        string $table,
        string $orderColumn = 'display_order',
        array $scope = []
    ): int {
        $this->assertIdentifier($table);
        $this->assertIdentifier($orderColumn);
        $this->assertScopeIdentifiers($scope);

        $sql = sprintf(
            'SELECT COALESCE(MAX(%s), 0) AS max_order
             FROM %s%s',
            $this->quoteIdentifier($orderColumn),
            $this->quoteIdentifier($table),
            $this->buildWhereClause($scope)
        );

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->buildScopeParams($scope));

        return (int)($stmt->fetchColumn() ?: 0);
    }

    /**
     * Clamps an insert position into the valid insert range.
     *
     * Insert range differs from move range:
     * - Empty scope => only valid insert position is 1
     * - Non-empty scope with max = 4 => valid insert positions are 1..5
     */
    private function clampInsertPosition(int $requestedPosition, int $maxPosition): int
    {
        if ($requestedPosition <= 1) {
            return 1;
        }

        if ($requestedPosition > ($maxPosition + 1)) {
            return $maxPosition + 1;
        }

        return $requestedPosition;
    }

    /**
     * Clamps a move position into the valid existing range.
     *
     * Move range is always between 1 and current max position.
     */
    private function clampMovePosition(int $requestedPosition, int $maxPosition): int
    {
        if ($maxPosition <= 1) {
            return 1;
        }

        if ($requestedPosition <= 1) {
            return 1;
        }

        if ($requestedPosition > $maxPosition) {
            return $maxPosition;
        }

        return $requestedPosition;
    }

    /**
     * Builds a WHERE clause from scope columns.
     *
     * Example:
     * - scope = ['product_id' => 10, 'is_deleted' => 0]
     * - result = ' WHERE `product_id` = :scope_product_id AND `is_deleted` = :scope_is_deleted'
     *
     * @param array<string, int|string|bool|null> $scope
     */
    private function buildWhereClause(array $scope): string
    {
        if ($scope === []) {
            return '';
        }

        return ' WHERE ' . implode(' AND ', $this->buildScopeComparisons($scope));
    }

    /**
     * Builds an additional AND clause from scope columns.
     *
     * This is used when the SQL statement already has its own WHERE clause.
     *
     * @param array<string, int|string|bool|null> $scope
     */
    private function buildAndScopeClause(array $scope): string
    {
        if ($scope === []) {
            return '';
        }

        return ' AND ' . implode(' AND ', $this->buildScopeComparisons($scope));
    }

    /**
     * Returns prepared-statement parameter values for scope columns.
     *
     * Null values are skipped because they are rendered as IS NULL in the SQL
     * (no placeholder is emitted for them), so including them would cause a
     * PDO HY093 "invalid parameter number" error.
     *
     * Example:
     * - scope = ['product_id' => 10, 'parent_id' => null]
     * - result = ['scope_product_id' => 10]   ← null entry is omitted
     *
     * @param array<string, int|string|bool|null> $scope
     * @return array<string, int|string|bool|null>
     */
    private function buildScopeParams(array $scope): array
    {
        $params = [];

        foreach ($scope as $column => $value) {
            if ($value === null) {
                // NULL columns use `IS NULL` in SQL — no placeholder is generated.
                continue;
            }
            $params['scope_' . $column] = $value;
        }

        return $params;
    }

    /**
     * Builds SQL comparison fragments for scope columns.
     *
     * Null values are translated to IS NULL instead of = :param.
     *
     * @param array<string, int|string|bool|null> $scope
     * @return list<string>
     */
    private function buildScopeComparisons(array $scope): array
    {
        $comparisons = [];

        foreach ($scope as $column => $value) {
            if ($value === null) {
                $comparisons[] = sprintf(
                    '%s IS NULL',
                    $this->quoteIdentifier($column)
                );
                continue;
            }

            $comparisons[] = sprintf(
                '%s = :scope_%s',
                $this->quoteIdentifier($column),
                $column
            );
        }

        return $comparisons;
    }

    /**
     * Validates a single SQL identifier such as table/column name.
     *
     * This is important because identifiers cannot be bound as PDO parameters.
     * They must be validated before being interpolated into SQL strings.
     */
    private function assertIdentifier(string $identifier): void
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $identifier)) {
            throw new PDOException(sprintf('Invalid SQL identifier: %s', $identifier));
        }
    }

    /**
     * Validates all scope column names.
     *
     * @param array<string, int|string|bool|null> $scope
     */
    private function assertScopeIdentifiers(array $scope): void
    {
        foreach (array_keys($scope) as $column) {
            $this->assertIdentifier($column);
        }
    }

    /**
     * Quotes a validated identifier using backticks.
     *
     * This method assumes validation already happened via assertIdentifier().
     */
    private function quoteIdentifier(string $identifier): string
    {
        return sprintf('`%s`', $identifier);
    }

    /**
     * Rolls back the current transaction only if one is active.
     */
    private function rollbackIfNeeded(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }
}

