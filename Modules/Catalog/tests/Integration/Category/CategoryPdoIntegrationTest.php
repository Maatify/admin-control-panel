<?php

declare(strict_types=1);

namespace Maatify\Catalog\Tests\Integration\Category;

use Maatify\Catalog\Category\Contract\CategoryCommandServiceInterface;
use Maatify\Catalog\Category\DTO\CreateCategoryDTO;
use Maatify\Catalog\Category\DTO\MoveCategoryDTO;
use Maatify\Catalog\Category\DTO\RestoreCategoryDTO;
use Maatify\Catalog\Category\DTO\SoftDeleteCategoryDTO;
use Maatify\Catalog\Category\Exception\CategoryCycleException;
use Maatify\Catalog\Category\Exception\CategoryHasNonDeletedChildrenException;
use Maatify\Catalog\Category\Infrastructure\Repository\PdoCategoryCommandRepository;
use Maatify\Catalog\Category\Infrastructure\Repository\PdoCategoryQueryReader;
use Maatify\Catalog\Category\Infrastructure\Repository\PdoCategoryTranslationCommandRepository;
use Maatify\Catalog\Category\Infrastructure\Transaction\PdoCategoryTransaction;
use Maatify\Catalog\Category\Service\CategoryCommandService;
use Maatify\Catalog\Tests\Integration\Support\CatalogMySqlIntegrationTestCase;
use Maatify\Catalog\Tests\Integration\Support\FixedCatalogClock;
use Maatify\Persistence\Pdo\Ordering\ScopedOrderingManager;
use Maatify\Catalog\Category\DTO\UpdateCategoryDisplayOrderDTO;
use PDO;
use PDOException;
use RuntimeException;
use Throwable;

final class CategoryPdoIntegrationTest extends CatalogMySqlIntegrationTestCase
{
    public function testApplicationClockAndAllStateReaderSupportLifecycleIdentity(): void
    {
        $clock = new FixedCatalogClock();
        $service = $this->service($this->connection(), $clock);
        $queryReader = new PdoCategoryQueryReader($this->connection());

        $categoryId = $service->create(new CreateCategoryDTO('root-category'));
        self::assertSame(1, $categoryId);
        self::assertSame(
            '2026-01-03 00:00:00',
            $queryReader->findById($categoryId)?->createdAt->format('Y-m-d H:i:s'),
        );

        $service->softDelete(new SoftDeleteCategoryDTO($categoryId));
        $deleted = $queryReader->findById($categoryId);
        self::assertNotNull($deleted->deletedAt);
        self::assertNull($queryReader->findActiveById($categoryId));

        $service->restore(new RestoreCategoryDTO($categoryId));
        $restored = $queryReader->findById($categoryId);
        self::assertSame($categoryId, $restored->id);
        self::assertNull($restored->deletedAt);
        self::assertSame('root-category', $restored->code);
    }

    public function testCompleteAncestorChainRejectsAnIndirectCycleOnMySql(): void
    {
        $service = $this->service($this->connection(), new FixedCatalogClock());
        $a = $service->create(new CreateCategoryDTO('cycle-a'));
        $b = $service->create(new CreateCategoryDTO('cycle-b', $a));
        $c = $service->create(new CreateCategoryDTO('cycle-c', $b));

        $this->expectException(CategoryCycleException::class);
        $service->move(new MoveCategoryDTO($a, $c));
    }

    public function testSoftDeleteChecksNonDeletedChildrenAndAllowsTheParentAfterChildDeletion(): void
    {
        $service = $this->service($this->connection(), new FixedCatalogClock());
        $parentId = $service->create(new CreateCategoryDTO('delete-parent'));
        $childId = $service->create(new CreateCategoryDTO('delete-child', $parentId));

        try {
            $service->softDelete(new SoftDeleteCategoryDTO($parentId));
            self::fail('A parent with a non-deleted child must not be soft-deleted.');
        } catch (CategoryHasNonDeletedChildrenException) {
            // The failed operation must have rolled back before the child deletion.
        }

        $service->softDelete(new SoftDeleteCategoryDTO($childId));
        $service->softDelete(new SoftDeleteCategoryDTO($parentId));

        $queryReader = new PdoCategoryQueryReader($this->connection());
        self::assertNull($queryReader->findActiveById($parentId));
        self::assertNotNull($queryReader->findById($parentId));
    }

    public function testSharedOrderingApiMovesRowsInsideTheSameParentScope(): void
    {
        $connection = $this->connection();
        $service = $this->service($connection, new FixedCatalogClock());
        $parentId = $service->create(new CreateCategoryDTO('ordering-parent'));
        $firstId = $service->create(new CreateCategoryDTO('ordering-first', $parentId));
        $secondId = $service->create(new CreateCategoryDTO('ordering-second', $parentId));

        $statement = $connection->prepare(
            'UPDATE `maa_catalog_categories` SET `display_order` = :display_order WHERE `id` = :id',
        );
        $statement->execute(['display_order' => 1, 'id' => $firstId]);
        $statement->execute(['display_order' => 2, 'id' => $secondId]);

        $service->updateDisplayOrder(new UpdateCategoryDisplayOrderDTO($secondId, 1));

        $ordersStatement = $connection->query(
            'SELECT `id`, `display_order` FROM `maa_catalog_categories` '
            . 'WHERE `parent_id` = ' . $parentId . ' ORDER BY `display_order`, `id`',
        );
        if ($ordersStatement === false) {
            self::fail('Unable to inspect Category ordering.');
        }
        /** @var array<int|string, int|string> $orders */
        $orders = $ordersStatement->fetchAll(PDO::FETCH_KEY_PAIR);
        self::assertSame(1, (int) $orders[$secondId]);
        self::assertSame(2, (int) $orders[$firstId]);
    }

    public function testSharedOrderingApiMovesRootRowsAndUpdatesTimestampAtomically(): void
    {
        $connection = $this->connection();
        $createService = $this->service($connection, new FixedCatalogClock('2026-01-03 00:00:00 UTC'));
        $firstId = $createService->create(new CreateCategoryDTO('root-ordering-first'));
        $secondId = $createService->create(new CreateCategoryDTO('root-ordering-second'));

        $statement = $connection->prepare(
            'UPDATE `maa_catalog_categories` '
            . 'SET `display_order` = :display_order, `updated_at` = :updated_at WHERE `id` = :id',
        );
        $statement->execute([
            'display_order' => 1,
            'updated_at' => '2026-01-01 00:00:00',
            'id' => $firstId,
        ]);
        $statement->execute([
            'display_order' => 2,
            'updated_at' => '2026-01-01 00:00:00',
            'id' => $secondId,
        ]);

        $updateService = $this->service($connection, new FixedCatalogClock('2026-01-04 00:00:00 UTC'));
        $updateService->updateDisplayOrder(new UpdateCategoryDisplayOrderDTO($secondId, 1));

        $ordersStatement = $connection->query(
            'SELECT `id`, `display_order` FROM `maa_catalog_categories` '
            . 'WHERE `parent_id` IS NULL ORDER BY `display_order`, `id`',
        );
        if ($ordersStatement === false) {
            self::fail('Unable to inspect root Category ordering.');
        }
        /** @var array<int|string, int|string> $orders */
        $orders = $ordersStatement->fetchAll(PDO::FETCH_KEY_PAIR);
        self::assertSame(1, (int) $orders[$secondId]);
        self::assertSame(2, (int) $orders[$firstId]);

        $timestampStatement = $connection->prepare(
            'SELECT `updated_at` FROM `maa_catalog_categories` WHERE `id` = :id',
        );
        $timestampStatement->execute(['id' => $secondId]);
        self::assertSame('2026-01-04 00:00:00', $timestampStatement->fetchColumn());
    }

    public function testTransactionPreservesTheOriginalThrowableWhenTransactionIsAlreadyClosed(): void
    {
        $transaction = new PdoCategoryTransaction($this->connection());
        $original = new RuntimeException('original transaction failure');

        $thrown = null;
        try {
            $transaction->run(function () use ($original): void {
                throw $original;
            });
        } catch (Throwable $thrown) {
        }
        self::assertSame($original, $thrown);
        self::assertFalse($this->connection()->inTransaction());

        $thrown = null;
        try {
            $transaction->run(function () use ($original): void {
                // Simulate a driver/operation that closes the transaction before
                // reporting its failure to the transaction adapter.
                $this->connection()->commit();
                throw $original;
            });
        } catch (Throwable $thrown) {
        }
        self::assertSame($original, $thrown);
        self::assertFalse($this->connection()->inTransaction());
    }

    public function testMoveWaitsOnLockedParentAndThenSucceedsAfterTheTransactionReleasesIt(): void
    {
        $connection = $this->connection();
        $service = $this->service($connection, new FixedCatalogClock());
        $sourceParentId = $service->create(new CreateCategoryDTO('source-parent'));
        $targetParentId = $service->create(new CreateCategoryDTO('target-parent'));
        $categoryId = $service->create(new CreateCategoryDTO('movable-category', $sourceParentId));

        $locker = $this->newConnection();
        $locker->beginTransaction();
        $lockingReader = new PdoCategoryQueryReader($locker);
        self::assertNotNull($lockingReader->findActiveByIdForUpdate($targetParentId));

        $blockedConnection = $this->newConnection();
        $blockedConnection->exec('SET SESSION innodb_lock_wait_timeout = 1');
        $blockedService = $this->service($blockedConnection, new FixedCatalogClock());

        try {
            $blockedService->move(new MoveCategoryDTO($categoryId, $targetParentId));
            self::fail('Moving under a locked parent must wait for the lock.');
        } catch (PDOException) {
            self::assertFalse($blockedConnection->inTransaction());
        } finally {
            if ($locker->inTransaction()) {
                $locker->rollBack();
            }
        }

        $blockedService->move(new MoveCategoryDTO($categoryId, $targetParentId));
        self::assertSame($targetParentId, (new PdoCategoryQueryReader($blockedConnection))->findById($categoryId)?->parentId);
        $blockedConnection = null;
    }

    public function testSoftDeleteWaitsOnTheLockedCategoryRow(): void
    {
        $connection = $this->connection();
        $service = $this->service($connection, new FixedCatalogClock());
        $categoryId = $service->create(new CreateCategoryDTO('locked-delete-category'));

        $locker = $this->newConnection();
        $locker->beginTransaction();
        self::assertNotNull((new PdoCategoryQueryReader($locker))->findActiveByIdForUpdate($categoryId));

        $blockedConnection = $this->newConnection();
        $blockedConnection->exec('SET SESSION innodb_lock_wait_timeout = 1');
        $blockedService = $this->service($blockedConnection, new FixedCatalogClock());

        try {
            $blockedService->softDelete(new SoftDeleteCategoryDTO($categoryId));
            self::fail('Soft delete must wait for a lock on the category row.');
        } catch (PDOException) {
            self::assertFalse($blockedConnection->inTransaction());
        } finally {
            if ($locker->inTransaction()) {
                $locker->rollBack();
            }
        }

        $blockedService->softDelete(new SoftDeleteCategoryDTO($categoryId));
        self::assertNull((new PdoCategoryQueryReader($blockedConnection))->findActiveById($categoryId));
        $blockedConnection = null;
    }

    private function service(PDO $connection, FixedCatalogClock $clock): CategoryCommandServiceInterface
    {
        return new CategoryCommandService(
            new PdoCategoryCommandRepository($connection, new ScopedOrderingManager()),
            new PdoCategoryQueryReader($connection),
            new PdoCategoryTranslationCommandRepository($connection),
            new PdoCategoryTransaction($connection),
            $clock,
        );
    }
}
