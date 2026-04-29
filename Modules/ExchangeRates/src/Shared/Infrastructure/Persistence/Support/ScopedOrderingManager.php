<?php

declare(strict_types=1);

namespace Maatify\ExchangeRates\Shared\Infrastructure\Persistence\Support;

use PDO;

/**
 * Manages display_order values within an optional scoped column.
 *
 * Scoped usage (maa_er_rates):
 *   scopeCol = 'provider_id', scopeVal = 42
 *   Rates are ordered independently within each provider.
 *
 * Global usage (maa_er_providers):
 *   scopeCol = null, scopeVal = null
 *   Providers share a single global display_order sequence.
 */
final class ScopedOrderingManager
{
    /**
     * Return the next available display_order value.
     *
     * @param  PDO         $pdo
     * @param  string      $table     Trusted table name constant — no user input
     * @param  string|null $scopeCol  Scope column name, or null for global ordering
     * @param  int|null    $scopeVal  Scope value, or null for global ordering
     * @return int                    MAX(display_order) + 1 within the scope, or 1 if empty
     */
    public function getNextPosition(PDO $pdo, string $table, ?string $scopeCol, ?int $scopeVal): int
    {
        if ($scopeCol !== null && $scopeVal !== null) {
            $stmt = $pdo->prepare(
                "SELECT COALESCE(MAX(`display_order`), 0) + 1
                   FROM `{$table}`
                  WHERE `{$scopeCol}` = :scope_val
                    AND `deleted_at`  IS NULL"
            );
            $stmt->execute(['scope_val' => $scopeVal]);
        } else {
            $stmt = $pdo->prepare(
                "SELECT COALESCE(MAX(`display_order`), 0) + 1
                   FROM `{$table}`
                  WHERE `deleted_at` IS NULL"
            );
            $stmt->execute();
        }

        $result = $stmt->fetchColumn();

        return (is_int($result) || is_string($result)) ? (int) $result : 1;
    }

    /**
     * Set a new display_order for a single row.
     *
     * Clamps the requested value between 1 and the current MAX within the scope.
     * Shifts conflicting rows to close the gap — wrapped in a transaction.
     *
     * @param  PDO         $pdo
     * @param  string      $table
     * @param  string|null $scopeCol      null for global ordering
     * @param  int|null    $scopeVal      null for global ordering
     * @param  int         $id            Row id to move
     * @param  int         $newOrder      Requested position (will be clamped 1..max)
     * @param  int         $currentOrder  Current display_order of the row
     * @return bool
     */
    public function moveWithinScope(
        PDO     $pdo,
        string  $table,
        ?string $scopeCol,
        ?int    $scopeVal,
        int     $id,
        int     $newOrder,
        int     $currentOrder,
    ): bool {
        // Fetch current MAX to clamp the upper bound
        $maxOrder = $this->currentMax($pdo, $table, $scopeCol, $scopeVal);

        // Clamp: 1 ≤ newOrder ≤ maxOrder
        $newOrder = max(1, min($newOrder, $maxOrder));

        if ($newOrder === $currentOrder) {
            return false;
        }

        // Build scope condition fragments
        [$scopeCondition, $scopeParams] = $this->buildScopeCondition($scopeCol, $scopeVal);

        $pdo->beginTransaction();

        try {
            if ($newOrder > $currentOrder) {
                // Moving down — shift rows between old+1 and new UP by 1
                $stmt = $pdo->prepare(
                    "UPDATE `{$table}`
                        SET `display_order` = `display_order` - 1
                      WHERE `id`             != :id
                        AND `deleted_at`     IS NULL
                        AND `display_order`  >  :old_order
                        AND `display_order` <=  :new_order
                        {$scopeCondition}"
                );
            } else {
                // Moving up — shift rows between new and old-1 DOWN by 1
                $stmt = $pdo->prepare(
                    "UPDATE `{$table}`
                        SET `display_order` = `display_order` + 1
                      WHERE `id`            != :id
                        AND `deleted_at`    IS NULL
                        AND `display_order` >= :new_order
                        AND `display_order`  < :old_order
                        {$scopeCondition}"
                );
            }

            $stmt->execute(array_merge([
                'id'        => $id,
                'old_order' => $currentOrder,
                'new_order' => $newOrder,
            ], $scopeParams));

            // Set the target row's new position
            $stmtSet = $pdo->prepare(
                "UPDATE `{$table}`
                    SET `display_order` = :new_order
                  WHERE `id`            = :id"
            );
            $stmtSet->execute(['new_order' => $newOrder, 'id' => $id]);

            $pdo->commit();

            return $stmtSet->rowCount() > 0;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    // =========================================================
    //  Private helpers
    // =========================================================

    /**
     * Return current MAX(display_order) within the scope.
     * Used for clamping upper bound before any move.
     */
    private function currentMax(PDO $pdo, string $table, ?string $scopeCol, ?int $scopeVal): int
    {
        if ($scopeCol !== null && $scopeVal !== null) {
            $stmt = $pdo->prepare(
                "SELECT COALESCE(MAX(`display_order`), 1)
                   FROM `{$table}`
                  WHERE `{$scopeCol}` = :scope_val
                    AND `deleted_at`  IS NULL"
            );
            $stmt->execute(['scope_val' => $scopeVal]);
        } else {
            $stmt = $pdo->prepare(
                "SELECT COALESCE(MAX(`display_order`), 1)
                   FROM `{$table}`
                  WHERE `deleted_at` IS NULL"
            );
            $stmt->execute();
        }

        $result = $stmt->fetchColumn();

        return (is_int($result) || is_string($result)) ? (int) $result : 1;
    }

    /**
     * Build the scope WHERE fragment and its bind params.
     *
     * @return array{string, array<string, int>}
     */
    private function buildScopeCondition(?string $scopeCol, ?int $scopeVal): array
    {
        if ($scopeCol !== null && $scopeVal !== null) {
            return ["AND `{$scopeCol}` = :scope_val", ['scope_val' => $scopeVal]];
        }

        return ['', []];
    }
}
