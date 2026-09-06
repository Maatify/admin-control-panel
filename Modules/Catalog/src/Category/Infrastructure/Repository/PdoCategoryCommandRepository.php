<?php

declare(strict_types=1);

namespace Maatify\Catalog\Category\Infrastructure\Repository;

use DateTimeImmutable;
use Maatify\Catalog\Category\Contract\CategoryCommandRepositoryInterface;
use Maatify\Catalog\Category\DTO\CreateCategoryDTO;
use Maatify\Catalog\Category\DTO\MoveCategoryDTO;
use Maatify\Catalog\Category\DTO\RestoreCategoryDTO;
use Maatify\Catalog\Category\DTO\SoftDeleteCategoryDTO;
use Maatify\Catalog\Category\DTO\UpdateCategoryDisplayOrderDTO;
use Maatify\Catalog\Category\DTO\UpdateCategoryStatusDTO;
use Maatify\Catalog\Category\Exception\CategoryTransactionException;
use Maatify\Persistence\Pdo\Ordering\ScopedOrderingConfig;
use Maatify\Persistence\Pdo\Ordering\ScopedOrderingManager;
use PDO;
use RuntimeException;

/** PDO write adapter for the Category persistence port. */
final readonly class PdoCategoryCommandRepository implements CategoryCommandRepositoryInterface
{
    private const CATEGORY_TABLE = 'maa_catalog_categories';

    public function __construct(
        private PDO $pdo,
        private ScopedOrderingManager $orderingManager,
    ) {}

    public function create(CreateCategoryDTO $command, DateTimeImmutable $occurredAt): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO `' . self::CATEGORY_TABLE . '` '
            . '(`parent_id`, `code`, `status`, `created_at`, `updated_at`, `deleted_at`) '
            . 'VALUES (:parent_id, :code, :status, :created_at, :updated_at, NULL)',
        );
        $timestamp = $this->formatTimestamp($occurredAt);
        $statement->execute([
            'parent_id' => $command->parentId,
            'code' => $command->code,
            'status' => $command->status->value,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        $id = $this->pdo->lastInsertId();
        if ($id === false || !ctype_digit($id) || (int) $id < 1) {
            throw new RuntimeException('Catalog Category AUTO_INCREMENT did not return a valid identity.');
        }

        return (int) $id;
    }

    public function move(MoveCategoryDTO $command, DateTimeImmutable $occurredAt): bool
    {
        $statement = $this->pdo->prepare(
            'UPDATE `' . self::CATEGORY_TABLE . '` '
            . 'SET `parent_id` = :parent_id, `updated_at` = :updated_at '
            . 'WHERE `id` = :id AND `deleted_at` IS NULL',
        );
        $statement->execute([
            'parent_id' => $command->parentId,
            'updated_at' => $this->formatTimestamp($occurredAt),
            'id' => $command->categoryId,
        ]);

        return $statement->rowCount() > 0;
    }

    public function softDelete(SoftDeleteCategoryDTO $command, DateTimeImmutable $occurredAt): bool
    {
        $statement = $this->pdo->prepare(
            'UPDATE `' . self::CATEGORY_TABLE . '` '
            . 'SET `deleted_at` = :deleted_at, `updated_at` = :updated_at '
            . 'WHERE `id` = :id AND `deleted_at` IS NULL',
        );
        $timestamp = $this->formatTimestamp($occurredAt);
        $statement->execute([
            'deleted_at' => $timestamp,
            'updated_at' => $timestamp,
            'id' => $command->categoryId,
        ]);

        return $statement->rowCount() > 0;
    }

    public function restore(RestoreCategoryDTO $command, DateTimeImmutable $occurredAt): bool
    {
        $statement = $this->pdo->prepare(
            'UPDATE `' . self::CATEGORY_TABLE . '` '
            . 'SET `deleted_at` = NULL, `updated_at` = :updated_at '
            . 'WHERE `id` = :id AND `deleted_at` IS NOT NULL',
        );
        $statement->execute([
            'updated_at' => $this->formatTimestamp($occurredAt),
            'id' => $command->categoryId,
        ]);

        return $statement->rowCount() > 0;
    }

    public function updateStatus(UpdateCategoryStatusDTO $command, DateTimeImmutable $occurredAt): bool
    {
        $statement = $this->pdo->prepare(
            'UPDATE `' . self::CATEGORY_TABLE . '` '
            . 'SET `status` = :status, `updated_at` = :updated_at '
            . 'WHERE `id` = :id AND `deleted_at` IS NULL',
        );
        $statement->execute([
            'status' => $command->status->value,
            'updated_at' => $this->formatTimestamp($occurredAt),
            'id' => $command->categoryId,
        ]);

        return $statement->rowCount() > 0;
    }

    public function updateDisplayOrder(
        UpdateCategoryDisplayOrderDTO $command,
        DateTimeImmutable $occurredAt,
    ): bool {
        $parentId = $this->activeParentId($command->categoryId);
        if ($parentId === false) {
            return false;
        }

        $moved = $parentId === null
            ? $this->moveWithinRootScope($command->categoryId, $command->displayOrder)
            : $this->orderingManager->moveWithinScope(
                $this->pdo,
                $this->orderingConfig(),
                $parentId,
                $command->categoryId,
                $command->displayOrder,
            );

        if (!$moved) {
            return false;
        }

        return $this->markDisplayOrderMutation($command->categoryId, $occurredAt);
    }

    private function activeParentId(int $categoryId): int|false|null
    {
        $statement = $this->pdo->prepare(
            'SELECT `parent_id` FROM `' . self::CATEGORY_TABLE . '` '
            . 'WHERE `id` = :id AND `deleted_at` IS NULL LIMIT 1',
        );
        $statement->execute(['id' => $categoryId]);
        $value = $statement->fetchColumn();

        if ($value === false) {
            return false;
        }
        if ($value === null) {
            return null;
        }
        return (int) $value;
    }

    private function orderingConfig(): ScopedOrderingConfig
    {
        return new ScopedOrderingConfig(
            table: self::CATEGORY_TABLE,
            scopeColumn: 'parent_id',
            idColumn: 'id',
            orderColumn: 'display_order',
            deletedAtColumn: 'deleted_at',
        );
    }

    private function markDisplayOrderMutation(int $categoryId, DateTimeImmutable $occurredAt): bool
    {
        $timestampStatement = $this->pdo->prepare(
            'UPDATE `' . self::CATEGORY_TABLE . '` '
            . 'SET `updated_at` = :updated_at '
            . 'WHERE `id` = :id AND `deleted_at` IS NULL',
        );
        $timestampStatement->execute([
            'updated_at' => $this->formatTimestamp($occurredAt),
            'id' => $categoryId,
        ]);

        if ($timestampStatement->rowCount() > 0) {
            return true;
        }

        // MySQL reports zero when the application timestamp is unchanged;
        // confirm that the ordered active row still exists in that case.
        $existsStatement = $this->pdo->prepare(
            'SELECT 1 FROM `' . self::CATEGORY_TABLE . '` '
            . 'WHERE `id` = :id AND `deleted_at` IS NULL LIMIT 1',
        );
        $existsStatement->execute(['id' => $categoryId]);

        return $existsStatement->fetchColumn() !== false;
    }

    /**
     * The stable Ordering API models non-null scopes. Category roots use the
     * schema's nullable parent scope, so this adapter applies the same
     * domain-specific root translation while all non-root movement delegates
     * to ScopedOrderingManager.
     */
    private function moveWithinRootScope(int $categoryId, int $newOrder): bool
    {
        if ($this->pdo->inTransaction()) {
            throw CategoryTransactionException::alreadyActive();
        }

        $this->pdo->beginTransaction();

        try {
            $lock = $this->pdo->query(
                'SELECT `id` FROM `' . self::CATEGORY_TABLE . '` '
                . 'WHERE `parent_id` IS NULL AND `deleted_at` IS NULL '
                . 'ORDER BY `display_order`, `id` FOR UPDATE',
            );
            if ($lock === false) {
                throw new RuntimeException('Unable to lock Catalog root ordering scope.');
            }

            $currentStatement = $this->pdo->prepare(
                'SELECT `display_order` FROM `' . self::CATEGORY_TABLE . '` '
                . 'WHERE `id` = :id AND `parent_id` IS NULL AND `deleted_at` IS NULL '
                . 'LIMIT 1 FOR UPDATE',
            );
            $currentStatement->execute(['id' => $categoryId]);
            $current = $currentStatement->fetchColumn();
            if ($current === false) {
                $this->pdo->rollBack();

                return false;
            }

            $maxStatement = $this->pdo->query(
                'SELECT COALESCE(MAX(`display_order`), 0) FROM `' . self::CATEGORY_TABLE . '` '
                . 'WHERE `parent_id` IS NULL AND `deleted_at` IS NULL',
            );
            if ($maxStatement === false) {
                throw new RuntimeException('Unable to read Catalog root ordering scope.');
            }

            $currentOrder = (int) $current;
            $maxOrder = (int) $maxStatement->fetchColumn();
            $targetOrder = min($newOrder, max(1, $maxOrder));

            if ($currentOrder !== $targetOrder) {
                if ($targetOrder < $currentOrder) {
                    $shift = $this->pdo->prepare(
                        'UPDATE `' . self::CATEGORY_TABLE . '` '
                        . 'SET `display_order` = `display_order` + 1 '
                        . 'WHERE `parent_id` IS NULL AND `deleted_at` IS NULL '
                        . 'AND `display_order` >= :new_order AND `display_order` < :current_order',
                    );
                } else {
                    $shift = $this->pdo->prepare(
                        'UPDATE `' . self::CATEGORY_TABLE . '` '
                        . 'SET `display_order` = `display_order` - 1 '
                        . 'WHERE `parent_id` IS NULL AND `deleted_at` IS NULL '
                        . 'AND `display_order` <= :new_order AND `display_order` > :current_order',
                    );
                }
                $shift->execute([
                    'new_order' => $targetOrder,
                    'current_order' => $currentOrder,
                ]);

                $target = $this->pdo->prepare(
                    'UPDATE `' . self::CATEGORY_TABLE . '` '
                    . 'SET `display_order` = :display_order '
                    . 'WHERE `id` = :id AND `parent_id` IS NULL AND `deleted_at` IS NULL',
                );
                $target->execute([
                    'display_order' => $targetOrder,
                    'id' => $categoryId,
                ]);
            }

            $this->pdo->commit();

            return true;
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    private function formatTimestamp(DateTimeImmutable $occurredAt): string
    {
        return $occurredAt->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }
}
