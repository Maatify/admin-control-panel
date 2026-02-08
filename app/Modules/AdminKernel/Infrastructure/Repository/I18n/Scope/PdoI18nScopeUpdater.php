<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Repository\I18n\Scope;

use Maatify\AdminKernel\Domain\I18n\Scope\Writer\I18nScopeUpdaterInterface;
use PDO;
use RuntimeException;

final readonly class PdoI18nScopeUpdater implements I18nScopeUpdaterInterface
{
    public function __construct(
        private PDO $pdo
    ) {}

    public function existsById(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM i18n_scopes WHERE id = :id LIMIT 1'
        );

        $stmt->execute(['id' => $id]);

        return $stmt->fetchColumn() !== false;
    }

    public function setActive(int $id, int $isActive): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE i18n_scopes SET is_active = :is_active WHERE id = :id'
        );

        $stmt->execute([
            'id' => $id,
            'is_active' => $isActive,
        ]);
    }

    public function existsByCode(string $code): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM i18n_scopes WHERE code = :code LIMIT 1'
        );
        $stmt->execute(['code' => $code]);

        return $stmt->fetchColumn() !== false;
    }

    public function isCodeInUse(string $code): bool
    {
        // 1) Check usage in i18n_keys
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM i18n_keys WHERE scope = :code LIMIT 1'
        );
        $stmt->execute(['code' => $code]);

        if ($stmt->fetchColumn() !== false) {
            return true;
        }

        // 2) Check usage in i18n_domain_scopes
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM i18n_domain_scopes WHERE scope_code = :code LIMIT 1'
        );
        $stmt->execute(['code' => $code]);

        return $stmt->fetchColumn() !== false;
    }


    public function getCurrentCode(int $id): string
    {
        $stmt = $this->pdo->prepare(
            'SELECT code FROM i18n_scopes WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);

        $code = $stmt->fetchColumn();

        if (!is_string($code)) {
            throw new RuntimeException('Failed to fetch current scope code');
        }

        return $code;
    }

    public function changeCode(int $id, string $newCode): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE i18n_scopes SET code = :code WHERE id = :id'
        );

        $ok = $stmt->execute([
            'id' => $id,
            'code' => $newCode
        ]);

        if ($ok === false) {
            throw new RuntimeException('Failed to change scope code');
        }
    }

    /**
     * Repositions scope sort_order using range-based updates.
     *
     * - Transaction-aware (joins existing transaction if present)
     * - Uses DB-level range updates (no full reindexing)
     * - Fail-soft by design (admin/UI ordering concern)
     *
     * This method is intended ONLY for UI ordering and MUST NOT
     * be used for security- or logic-critical ordering.
     */
    public function repositionSortOrder(
        int $id,
        int $newPosition
    ): void {
        $ownsTransaction = ! $this->pdo->inTransaction();

        if ($ownsTransaction) {
            $this->pdo->beginTransaction();
        }

        try {
            // 1) Fetch current sort_order
            $stmt = $this->pdo->prepare(
                'SELECT sort_order FROM i18n_scopes WHERE id = :id'
            );
            $stmt->execute(['id' => $id]);

            $currentSort = $stmt->fetchColumn();

            if (!is_numeric($currentSort)) {
                // fail-soft: invalid scope id
                if ($ownsTransaction && $this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }
                return;
            }

            $currentSort = (int)$currentSort;

            // No-op
            if ($currentSort === $newPosition) {
                if ($ownsTransaction) {
                    $this->pdo->commit();
                }
                return;
            }

            if ($newPosition < $currentSort) {
                // Move up
                $stmt = $this->pdo->prepare(
                    '
                UPDATE i18n_scopes
                SET sort_order = sort_order + 1
                WHERE sort_order >= :target
                  AND sort_order < :current
                '
                );

                if ($stmt instanceof \PDOStatement) {
                    $stmt->execute([
                        'target'  => $newPosition,
                        'current' => $currentSort,
                    ]);
                }
            } elseif ($newPosition > $currentSort) {
                // Move down
                $stmt = $this->pdo->prepare(
                    '
                UPDATE i18n_scopes
                SET sort_order = sort_order - 1
                WHERE sort_order > :current
                  AND sort_order <= :target
                '
                );

                if ($stmt instanceof \PDOStatement) {
                    $stmt->execute([
                        'current' => $currentSort,
                        'target'  => $newPosition,
                    ]);
                }
            }

            // 2) Place scope at target position
            $stmt = $this->pdo->prepare(
                '
            UPDATE i18n_scopes
            SET sort_order = :target
            WHERE id = :id
            '
            );

            if ($stmt instanceof \PDOStatement) {
                $stmt->execute([
                    'target' => $newPosition,
                    'id'     => $id,
                ]);
            }

            if ($ownsTransaction) {
                $this->pdo->commit();
            }
        } catch (\Throwable $e) {
            if ($ownsTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            // fail-soft: ordering must never break admin flow
            return;
        }
    }

    public function updateMetadata(
        int $id,
        ?string $name,
        ?string $description
    ): void {
        $fields = [];
        $params = ['id' => $id];

        if ($name !== null) {
            $fields[] = 'name = :name';
            $params['name'] = $name;
        }

        if ($description !== null) {
            $fields[] = 'description = :description';
            $params['description'] = $description;
        }

        // Safety guard: controller guarantees at least one field,
        // this is only a defensive fallback
        // Guard: must update at least one field
        if ($fields === []) {
            return; // or throw — controller already guards this
        }

        $sql = sprintf(
            'UPDATE i18n_scopes SET %s WHERE id = :id',
            implode(', ', $fields)
        );

        $stmt = $this->pdo->prepare($sql);

        if ($stmt instanceof \PDOStatement) {
            $stmt->execute($params);
        }
    }
}
