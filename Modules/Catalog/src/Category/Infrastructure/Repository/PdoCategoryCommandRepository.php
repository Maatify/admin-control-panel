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
use Maatify\Catalog\Category\Exception\CategoryPersistenceException;
use Maatify\Persistence\Pdo\Ordering\ScopedOrderingConfig;
use Maatify\Persistence\Pdo\Ordering\ScopedOrderingManager;
use PDO;

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
            throw CategoryPersistenceException::invalidAutoIncrementIdentity();
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

        $moved = $this->orderingManager->moveWithinScope(
            $this->pdo,
            $this->orderingConfig(),
            $parentId,
            $command->categoryId,
            $command->displayOrder,
            $this->formatTimestamp($occurredAt),
        );

        return $moved;
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
            nullableScope: true,
            updatedAtColumn: 'updated_at',
        );
    }

    private function formatTimestamp(DateTimeImmutable $occurredAt): string
    {
        return $occurredAt->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }
}
