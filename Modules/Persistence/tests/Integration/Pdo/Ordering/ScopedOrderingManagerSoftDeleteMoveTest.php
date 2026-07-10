<?php

declare(strict_types=1);

namespace Maatify\Persistence\Tests\Integration\Pdo\Ordering;

use Maatify\Persistence\Pdo\Ordering\ScopedOrderingConfig;
use Maatify\Persistence\Pdo\Ordering\ScopedOrderingManager;
use Maatify\Persistence\Tests\Support\MySql\MySqlIntegrationTestCase;
use Maatify\Persistence\Tests\Support\MySql\OrderingSchemaManager;
use Maatify\Persistence\Tests\Support\MySql\OrderingStateReader;

final class ScopedOrderingManagerSoftDeleteMoveTest extends MySqlIntegrationTestCase
{
    private const DELETED_AT = '2026-01-01 00:00:00';

    private ScopedOrderingManager $manager;
    private OrderingStateReader $reader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = new ScopedOrderingManager();
        $this->reader = new OrderingStateReader($this->pdo());
    }

    public function testGlobalDeletedTargetWithDefaultFilteringReturnsFalse(): void
    {
        $active1 = $this->fixture->insertGlobal(1);
        $deleted = $this->fixture->insertGlobal(2, deletedAt: self::DELETED_AT);
        $active3 = $this->fixture->insertGlobal(3);
        $before = $this->reader->globalOrdersById();

        $result = $this->manager->moveWithinScope($this->pdo(), $this->globalConfig(), null, $deleted, 1);

        self::assertFalse($result);
        self::assertSame($before, $this->reader->globalOrdersById());
        self::assertSame([$active1 => 1, $deleted => 2, $active3 => 3], $before);
        self::assertFalse($this->pdo()->inTransaction());
    }

    public function testGlobalDeletedNonTargetIsNotShifted(): void
    {
        $target = $this->fixture->insertGlobal(1);
        $deleted = $this->fixture->insertGlobal(2, deletedAt: self::DELETED_AT);
        $active = $this->fixture->insertGlobal(3);

        $result = $this->manager->moveWithinScope($this->pdo(), $this->globalConfig(), null, $target, 3);

        self::assertTrue($result);
        self::assertSame([$target => 3, $deleted => 2, $active => 2], $this->reader->globalOrdersById());
        self::assertFalse($this->pdo()->inTransaction());
    }

    public function testGlobalFilteringDisabledIncludesDeletedRowsInMove(): void
    {
        $active1 = $this->fixture->insertGlobal(1);
        $deletedTarget = $this->fixture->insertGlobal(2, deletedAt: self::DELETED_AT);
        $active3 = $this->fixture->insertGlobal(3);

        $result = $this->manager->moveWithinScope($this->pdo(), $this->globalConfigWithoutSoftDeletes(), null, $deletedTarget, 1);

        self::assertTrue($result);
        self::assertSame([$active1 => 2, $deletedTarget => 1, $active3 => 3], $this->reader->globalOrdersById());
        self::assertFalse($this->pdo()->inTransaction());
    }

    public function testScopedDeletedTargetWithDefaultFilteringReturnsFalse(): void
    {
        $a1 = $this->fixture->insertScoped('scope-a', 1);
        $deleted = $this->fixture->insertScoped('scope-a', 2, deletedAt: self::DELETED_AT);
        $b1 = $this->fixture->insertScoped('scope-b', 1);
        $beforeA = $this->reader->scopedOrdersById('scope-a');
        $beforeB = $this->reader->scopedOrdersById('scope-b');

        $result = $this->manager->moveWithinScope($this->pdo(), $this->scopedConfig(), 'scope-a', $deleted, 1);

        self::assertFalse($result);
        self::assertSame($beforeA, $this->reader->scopedOrdersById('scope-a'));
        self::assertSame($beforeB, $this->reader->scopedOrdersById('scope-b'));
        self::assertSame([$a1 => 1, $deleted => 2], $beforeA);
        self::assertSame([$b1 => 1], $beforeB);
        self::assertFalse($this->pdo()->inTransaction());
    }

    public function testScopedDeletedNonTargetIsNotShifted(): void
    {
        $target = $this->fixture->insertScoped('scope-a', 1);
        $deleted = $this->fixture->insertScoped('scope-a', 2, deletedAt: self::DELETED_AT);
        $active = $this->fixture->insertScoped('scope-a', 3);
        $b = $this->fixture->insertScoped('scope-b', 3);

        $result = $this->manager->moveWithinScope($this->pdo(), $this->scopedConfig(), 'scope-a', $target, 3);

        self::assertTrue($result);
        self::assertSame([$target => 3, $deleted => 2, $active => 2], $this->reader->scopedOrdersById('scope-a'));
        self::assertSame([$b => 3], $this->reader->scopedOrdersById('scope-b'));
        self::assertFalse($this->pdo()->inTransaction());
    }

    public function testScopedFilteringDisabledIncludesDeletedRowsInRequestedScopeOnly(): void
    {
        $active1 = $this->fixture->insertScoped('scope-a', 1);
        $deletedTarget = $this->fixture->insertScoped('scope-a', 2, deletedAt: self::DELETED_AT);
        $active3 = $this->fixture->insertScoped('scope-a', 3);
        $bDeleted = $this->fixture->insertScoped('scope-b', 2, deletedAt: self::DELETED_AT);
        $bActive = $this->fixture->insertScoped('scope-b', 3);

        $result = $this->manager->moveWithinScope($this->pdo(), $this->scopedConfigWithoutSoftDeletes(), 'scope-a', $deletedTarget, 1);

        self::assertTrue($result);
        self::assertSame([$active1 => 2, $deletedTarget => 1, $active3 => 3], $this->reader->scopedOrdersById('scope-a'));
        self::assertSame([$bDeleted => 2, $bActive => 3], $this->reader->scopedOrdersById('scope-b'));
        self::assertFalse($this->pdo()->inTransaction());
    }

    private function globalConfig(): ScopedOrderingConfig
    {
        return new ScopedOrderingConfig(table: OrderingSchemaManager::GLOBAL_TABLE);
    }

    private function globalConfigWithoutSoftDeletes(): ScopedOrderingConfig
    {
        return new ScopedOrderingConfig(table: OrderingSchemaManager::GLOBAL_TABLE, deletedAtColumn: null);
    }

    private function scopedConfig(): ScopedOrderingConfig
    {
        return new ScopedOrderingConfig(table: OrderingSchemaManager::SCOPED_TABLE, scopeColumn: 'scope_key');
    }

    private function scopedConfigWithoutSoftDeletes(): ScopedOrderingConfig
    {
        return new ScopedOrderingConfig(table: OrderingSchemaManager::SCOPED_TABLE, scopeColumn: 'scope_key', deletedAtColumn: null);
    }
}
