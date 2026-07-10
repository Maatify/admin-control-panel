<?php

declare(strict_types=1);

namespace Maatify\Persistence\Tests\Integration\Pdo\Ordering;

use Maatify\Persistence\Exception\InvalidOrderingOperationException;
use Maatify\Persistence\Pdo\Ordering\ScopedOrderingConfig;
use Maatify\Persistence\Pdo\Ordering\ScopedOrderingManager;
use Maatify\Persistence\Tests\Support\MySql\MySqlIntegrationTestCase;
use Maatify\Persistence\Tests\Support\MySql\OrderingSchemaManager;
use Maatify\Persistence\Tests\Support\MySql\OrderingStateReader;

final class ScopedOrderingManagerScopedMoveTest extends MySqlIntegrationTestCase
{
    private ScopedOrderingManager $manager;
    private OrderingStateReader $reader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = new ScopedOrderingManager();
        $this->reader = new OrderingStateReader($this->pdo());
    }

    public function testMoveUpwardInsideOneScopeDoesNotChangeOtherScope(): void
    {
        $a = $this->insertScopedOrders('scope-a', 1, 2, 3, 4, 5);
        $b = $this->insertScopedOrders('scope-b', 1, 2, 3);
        $beforeB = $this->reader->scopedOrdersById('scope-b');

        $result = $this->manager->moveWithinScope($this->pdo(), $this->scopedConfig(), 'scope-a', $a[4], 2);

        self::assertTrue($result);
        self::assertSame([$a[0] => 1, $a[1] => 3, $a[2] => 4, $a[3] => 5, $a[4] => 2], $this->reader->scopedOrdersById('scope-a'));
        self::assertSame($beforeB, $this->reader->scopedOrdersById('scope-b'));
        self::assertSame([$b[0] => 1, $b[1] => 2, $b[2] => 3], $beforeB);
        self::assertFalse($this->pdo()->inTransaction());
    }

    public function testMoveDownwardInsideOneScopeDoesNotChangeOtherScope(): void
    {
        $a = $this->insertScopedOrders('scope-a', 1, 2, 3, 4, 5);
        $b = $this->insertScopedOrders('scope-b', 10, 11);

        $result = $this->manager->moveWithinScope($this->pdo(), $this->scopedConfig(), 'scope-a', $a[1], 5);

        self::assertTrue($result);
        self::assertSame([$a[0] => 1, $a[1] => 5, $a[2] => 2, $a[3] => 3, $a[4] => 4], $this->reader->scopedOrdersById('scope-a'));
        self::assertSame([$b[0] => 10, $b[1] => 11], $this->reader->scopedOrdersById('scope-b'));
        self::assertFalse($this->pdo()->inTransaction());
    }

    public function testTargetQueriedThroughWrongScopeReturnsFalse(): void
    {
        $a = $this->insertScopedOrders('scope-a', 1, 2);
        $b = $this->insertScopedOrders('scope-b', 1, 2);
        $beforeA = $this->reader->scopedOrdersById('scope-a');
        $beforeB = $this->reader->scopedOrdersById('scope-b');

        $result = $this->manager->moveWithinScope($this->pdo(), $this->scopedConfig(), 'scope-b', $a[0], 2);

        self::assertFalse($result);
        self::assertSame($beforeA, $this->reader->scopedOrdersById('scope-a'));
        self::assertSame($beforeB, $this->reader->scopedOrdersById('scope-b'));
        self::assertSame([$b[0] => 1, $b[1] => 2], $beforeB);
        self::assertFalse($this->pdo()->inTransaction());
    }

    public function testClampUsesRequestedScopeMaximum(): void
    {
        $a = $this->insertScopedOrders('scope-a', 1, 2, 3);
        $b = $this->insertScopedOrders('scope-b', 10, 20);

        $result = $this->manager->moveWithinScope($this->pdo(), $this->scopedConfig(), 'scope-a', $a[0], 999);

        self::assertTrue($result);
        self::assertSame([$a[0] => 3, $a[1] => 1, $a[2] => 2], $this->reader->scopedOrdersById('scope-a'));
        self::assertSame([$b[0] => 10, $b[1] => 20], $this->reader->scopedOrdersById('scope-b'));
        self::assertFalse($this->pdo()->inTransaction());
    }

    public function testIntegerScopeValueMovesPersistedRows(): void
    {
        $ids = $this->insertScopedOrders(42, 1, 2, 3);

        $result = $this->manager->moveWithinScope($this->pdo(), $this->scopedConfig(), 42, $ids[2], 1);

        self::assertTrue($result);
        self::assertSame([$ids[0] => 2, $ids[1] => 3, $ids[2] => 1], $this->reader->scopedOrdersById(42));
        self::assertFalse($this->pdo()->inTransaction());
    }

    public function testStringScopeValueMovesPersistedRows(): void
    {
        $ids = $this->insertScopedOrders('string-scope', 1, 2, 3);

        $result = $this->manager->moveWithinScope($this->pdo(), $this->scopedConfig(), 'string-scope', $ids[0], 3);

        self::assertTrue($result);
        self::assertSame([$ids[0] => 3, $ids[1] => 1, $ids[2] => 2], $this->reader->scopedOrdersById('string-scope'));
        self::assertFalse($this->pdo()->inTransaction());
    }

    public function testScopedNoOpLeavesBothScopesUnchanged(): void
    {
        $a = $this->insertScopedOrders('scope-a', 1, 2, 3);
        $b = $this->insertScopedOrders('scope-b', 4, 5);
        $beforeA = $this->reader->scopedOrdersById('scope-a');
        $beforeB = $this->reader->scopedOrdersById('scope-b');

        $result = $this->manager->moveWithinScope($this->pdo(), $this->scopedConfig(), 'scope-a', $a[1], 2);

        self::assertTrue($result);
        self::assertSame($beforeA, $this->reader->scopedOrdersById('scope-a'));
        self::assertSame($beforeB, $this->reader->scopedOrdersById('scope-b'));
        self::assertSame([$b[0] => 4, $b[1] => 5], $beforeB);
        self::assertFalse($this->pdo()->inTransaction());
    }

    public function testScopedConfigurationRejectsNullScopeValueBeforeTransactionBegins(): void
    {
        $ids = $this->insertScopedOrders('scope-a', 1);
        $before = $this->reader->scopedOrdersById('scope-a');

        try {
            $this->manager->moveWithinScope($this->pdo(), $this->scopedConfig(), null, $ids[0], 1);
            self::fail('Expected invalid ordering operation exception.');
        } catch (InvalidOrderingOperationException) {
            self::assertSame($before, $this->reader->scopedOrdersById('scope-a'));
            self::assertFalse($this->pdo()->inTransaction());
        }
    }

    /**
     * @return list<int>
     */
    private function insertScopedOrders(int|string $scopeValue, int ...$orders): array
    {
        $ids = [];
        foreach ($orders as $order) {
            $ids[] = $this->fixture->insertScoped($scopeValue, $order);
        }

        return $ids;
    }

    private function scopedConfig(): ScopedOrderingConfig
    {
        return new ScopedOrderingConfig(table: OrderingSchemaManager::SCOPED_TABLE, scopeColumn: 'scope_key');
    }
}
