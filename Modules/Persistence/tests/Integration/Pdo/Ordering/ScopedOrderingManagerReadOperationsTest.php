<?php

declare(strict_types=1);

namespace Maatify\Persistence\Tests\Integration\Pdo\Ordering;

use Maatify\Persistence\Exception\InvalidOrderingOperationException;
use Maatify\Persistence\Pdo\Ordering\ScopedOrderingConfig;
use Maatify\Persistence\Pdo\Ordering\ScopedOrderingManager;
use Maatify\Persistence\Tests\Support\MySql\MySqlIntegrationTestCase;
use Maatify\Persistence\Tests\Support\MySql\OrderingSchemaManager;

final class ScopedOrderingManagerReadOperationsTest extends MySqlIntegrationTestCase
{
    private ScopedOrderingManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = new ScopedOrderingManager();
    }

    public function testGetNextPositionReturnsOneForEmptyGlobalTable(): void
    {
        self::assertSame(1, $this->manager->getNextPosition($this->pdo(), $this->globalConfig()));
    }

    public function testGetNextPositionReturnsMaxPlusOneForContiguousGlobalRows(): void
    {
        $this->fixture->insertGlobal(1);
        $this->fixture->insertGlobal(2);
        $this->fixture->insertGlobal(3);

        self::assertSame(4, $this->manager->getNextPosition($this->pdo(), $this->globalConfig()));
    }

    public function testGetNextPositionUsesMaximumPositionInsteadOfFirstGapForGlobalRows(): void
    {
        $this->fixture->insertGlobal(1);
        $this->fixture->insertGlobal(3);

        self::assertSame(4, $this->manager->getNextPosition($this->pdo(), $this->globalConfig()));
    }

    public function testGetNextPositionIgnoresSoftDeletedGlobalMaximumWhenSoftDeleteColumnIsConfigured(): void
    {
        $this->fixture->insertGlobal(2);
        $this->fixture->insertGlobal(9, deletedAt: '2026-01-01 00:00:00');

        self::assertSame(3, $this->manager->getNextPosition($this->pdo(), $this->globalConfig()));
    }

    public function testGetNextPositionIncludesSoftDeletedGlobalMaximumWhenSoftDeleteFilteringIsDisabled(): void
    {
        $this->fixture->insertGlobal(2);
        $this->fixture->insertGlobal(9, deletedAt: '2026-01-01 00:00:00');

        self::assertSame(10, $this->manager->getNextPosition($this->pdo(), $this->globalConfigWithoutSoftDeletes()));
    }

    public function testGetNextPositionReturnsOneForEmptyRequestedScope(): void
    {
        $this->fixture->insertScoped('other-scope', 6);

        self::assertSame(1, $this->manager->getNextPosition($this->pdo(), $this->scopedConfig(), 'requested-scope'));
    }

    public function testGetNextPositionMaintainsIndependentMaximumsForScopes(): void
    {
        $this->fixture->insertScoped('scope-a', 1);
        $this->fixture->insertScoped('scope-a', 4);
        $this->fixture->insertScoped('scope-b', 8);

        self::assertSame(5, $this->manager->getNextPosition($this->pdo(), $this->scopedConfig(), 'scope-a'));
        self::assertSame(9, $this->manager->getNextPosition($this->pdo(), $this->scopedConfig(), 'scope-b'));
    }

    public function testGetNextPositionAcceptsIntegerScopeValueThroughPreparedStatement(): void
    {
        $this->fixture->insertScoped(42, 7);
        $this->fixture->insertScoped(43, 11);

        self::assertSame(8, $this->manager->getNextPosition($this->pdo(), $this->scopedConfig(), 42));
    }

    public function testGetNextPositionAcceptsStringScopeValueThroughPreparedStatement(): void
    {
        $this->fixture->insertScoped('alpha', 3);
        $this->fixture->insertScoped('beta', 10);

        self::assertSame(4, $this->manager->getNextPosition($this->pdo(), $this->scopedConfig(), 'alpha'));
    }

    public function testGetNextPositionIgnoresSoftDeletedRowsOnlyInsideRequestedScope(): void
    {
        $this->fixture->insertScoped('scope-a', 2);
        $this->fixture->insertScoped('scope-a', 9, deletedAt: '2026-01-01 00:00:00');
        $this->fixture->insertScoped('scope-b', 12, deletedAt: '2026-01-01 00:00:00');
        $this->fixture->insertScoped('scope-b', 4);

        self::assertSame(3, $this->manager->getNextPosition($this->pdo(), $this->scopedConfig(), 'scope-a'));
        self::assertSame(5, $this->manager->getNextPosition($this->pdo(), $this->scopedConfig(), 'scope-b'));
    }

    public function testGetNextPositionRejectsScopedConfigurationWithoutScopeValue(): void
    {
        $this->expectException(InvalidOrderingOperationException::class);

        $this->manager->getNextPosition($this->pdo(), $this->scopedConfig());
    }

    public function testGetNextPositionRejectsGlobalConfigurationWithScopeValue(): void
    {
        $this->expectException(InvalidOrderingOperationException::class);

        $this->manager->getNextPosition($this->pdo(), $this->globalConfig(), 'unexpected-scope');
    }

    public function testRowExistsInScopeReturnsTrueForExistingActiveGlobalRow(): void
    {
        $id = $this->fixture->insertGlobal(1);

        self::assertTrue($this->manager->rowExistsInScope($this->pdo(), $this->globalConfig(), null, $id));
    }

    public function testRowExistsInScopeReturnsFalseForMissingGlobalRow(): void
    {
        self::assertFalse($this->manager->rowExistsInScope($this->pdo(), $this->globalConfig(), null, 999));
    }

    public function testRowExistsInScopeReturnsFalseForZeroGlobalId(): void
    {
        self::assertFalse($this->manager->rowExistsInScope($this->pdo(), $this->globalConfig(), null, 0));
    }

    public function testRowExistsInScopeReturnsFalseForNegativeGlobalId(): void
    {
        self::assertFalse($this->manager->rowExistsInScope($this->pdo(), $this->globalConfig(), null, -1));
    }

    public function testRowExistsInScopeReturnsFalseForSoftDeletedGlobalRowWithDefaultFiltering(): void
    {
        $id = $this->fixture->insertGlobal(1, deletedAt: '2026-01-01 00:00:00');

        self::assertFalse($this->manager->rowExistsInScope($this->pdo(), $this->globalConfig(), null, $id));
    }

    public function testRowExistsInScopeReturnsTrueForSoftDeletedGlobalRowWhenFilteringIsDisabled(): void
    {
        $id = $this->fixture->insertGlobal(1, deletedAt: '2026-01-01 00:00:00');

        self::assertTrue($this->manager->rowExistsInScope($this->pdo(), $this->globalConfigWithoutSoftDeletes(), null, $id));
    }

    public function testRowExistsInScopeReturnsTrueForExistingRowInsideRequestedScope(): void
    {
        $id = $this->fixture->insertScoped('scope-a', 1);

        self::assertTrue($this->manager->rowExistsInScope($this->pdo(), $this->scopedConfig(), 'scope-a', $id));
    }

    public function testRowExistsInScopeReturnsFalseForExistingRowQueriedUsingDifferentScope(): void
    {
        $id = $this->fixture->insertScoped('scope-a', 1);

        self::assertFalse($this->manager->rowExistsInScope($this->pdo(), $this->scopedConfig(), 'scope-b', $id));
    }

    public function testRowExistsInScopeDoesNotExposeScopeBRowInScopeA(): void
    {
        $id = $this->fixture->insertScoped('scope-b', 1);

        self::assertFalse($this->manager->rowExistsInScope($this->pdo(), $this->scopedConfig(), 'scope-a', $id));
    }

    public function testRowExistsInScopeAcceptsIntegerScopeValue(): void
    {
        $id = $this->fixture->insertScoped(123, 1);

        self::assertTrue($this->manager->rowExistsInScope($this->pdo(), $this->scopedConfig(), 123, $id));
    }

    public function testRowExistsInScopeAcceptsStringScopeValue(): void
    {
        $id = $this->fixture->insertScoped('string-scope', 1);

        self::assertTrue($this->manager->rowExistsInScope($this->pdo(), $this->scopedConfig(), 'string-scope', $id));
    }

    public function testRowExistsInScopeTreatsSoftDeletedScopedRowAsMissing(): void
    {
        $id = $this->fixture->insertScoped('scope-a', 1, deletedAt: '2026-01-01 00:00:00');

        self::assertFalse($this->manager->rowExistsInScope($this->pdo(), $this->scopedConfig(), 'scope-a', $id));
    }

    public function testRowExistsInScopeRejectsScopedConfigurationWithoutScopeValue(): void
    {
        $this->expectException(InvalidOrderingOperationException::class);

        $this->manager->rowExistsInScope($this->pdo(), $this->scopedConfig(), null, 1);
    }

    public function testRowExistsInScopeRejectsGlobalConfigurationWithScopeValue(): void
    {
        $this->expectException(InvalidOrderingOperationException::class);

        $this->manager->rowExistsInScope($this->pdo(), $this->globalConfig(), 'unexpected-scope', 1);
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
}
