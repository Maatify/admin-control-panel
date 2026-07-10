<?php

declare(strict_types=1);

namespace Maatify\Persistence\Tests\Integration\Pdo\Ordering;

use Maatify\Persistence\Exception\InvalidOrderingOperationException;
use Maatify\Persistence\Pdo\Ordering\ScopedOrderingConfig;
use Maatify\Persistence\Pdo\Ordering\ScopedOrderingManager;
use Maatify\Persistence\Tests\Support\MySql\MySqlIntegrationTestCase;
use Maatify\Persistence\Tests\Support\MySql\OrderingSchemaManager;
use Maatify\Persistence\Tests\Support\MySql\OrderingStateReader;

final class ScopedOrderingManagerGlobalMoveTest extends MySqlIntegrationTestCase
{
    private ScopedOrderingManager $manager;
    private OrderingStateReader $reader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = new ScopedOrderingManager();
        $this->reader = new OrderingStateReader($this->pdo());
    }

    public function testMoveUpwardShiftsAffectedGlobalRange(): void
    {
        $ids = $this->insertGlobalOrders(1, 2, 3, 4, 5);

        $result = $this->manager->moveWithinScope($this->pdo(), $this->globalConfig(), null, $ids[4], 2);

        self::assertTrue($result);
        self::assertSame([$ids[0] => 1, $ids[1] => 3, $ids[2] => 4, $ids[3] => 5, $ids[4] => 2], $this->reader->globalOrdersById());
        self::assertFalse($this->pdo()->inTransaction());
    }

    public function testMoveDownwardShiftsAffectedGlobalRange(): void
    {
        $ids = $this->insertGlobalOrders(1, 2, 3, 4, 5);

        $result = $this->manager->moveWithinScope($this->pdo(), $this->globalConfig(), null, $ids[1], 5);

        self::assertTrue($result);
        self::assertSame([$ids[0] => 1, $ids[1] => 5, $ids[2] => 2, $ids[3] => 3, $ids[4] => 4], $this->reader->globalOrdersById());
        self::assertFalse($this->pdo()->inTransaction());
    }

    public function testMoveFirstToLast(): void
    {
        $ids = $this->insertGlobalOrders(1, 2, 3, 4, 5);

        $result = $this->manager->moveWithinScope($this->pdo(), $this->globalConfig(), null, $ids[0], 5);

        self::assertTrue($result);
        self::assertSame([$ids[0] => 5, $ids[1] => 1, $ids[2] => 2, $ids[3] => 3, $ids[4] => 4], $this->reader->globalOrdersById());
        self::assertFalse($this->pdo()->inTransaction());
    }

    public function testMoveLastToFirst(): void
    {
        $ids = $this->insertGlobalOrders(1, 2, 3, 4, 5);

        $result = $this->manager->moveWithinScope($this->pdo(), $this->globalConfig(), null, $ids[4], 1);

        self::assertTrue($result);
        self::assertSame([$ids[0] => 2, $ids[1] => 3, $ids[2] => 4, $ids[3] => 5, $ids[4] => 1], $this->reader->globalOrdersById());
        self::assertFalse($this->pdo()->inTransaction());
    }

    public function testNoOpLeavesGlobalRowsUnchanged(): void
    {
        $ids = $this->insertGlobalOrders(1, 2, 3, 4, 5);
        $before = $this->reader->globalOrdersById();

        $result = $this->manager->moveWithinScope($this->pdo(), $this->globalConfig(), null, $ids[2], 3);

        self::assertTrue($result);
        self::assertSame($before, $this->reader->globalOrdersById());
        self::assertFalse($this->pdo()->inTransaction());
    }

    public function testClampAboveMaximumMovesToCurrentMaximum(): void
    {
        $ids = $this->insertGlobalOrders(1, 2, 3);

        $result = $this->manager->moveWithinScope($this->pdo(), $this->globalConfig(), null, $ids[0], 999);

        self::assertTrue($result);
        self::assertSame([$ids[0] => 3, $ids[1] => 1, $ids[2] => 2], $this->reader->globalOrdersById());
        self::assertFalse($this->pdo()->inTransaction());
    }

    public function testMissingTargetReturnsFalseAndLeavesRowsUnchanged(): void
    {
        $ids = $this->insertGlobalOrders(1, 2, 3);
        $before = $this->reader->globalOrdersById();
        $missingId = $ids[2] + 100;

        $result = $this->manager->moveWithinScope($this->pdo(), $this->globalConfig(), null, $missingId, 2);

        self::assertFalse($result);
        self::assertSame($before, $this->reader->globalOrdersById());
        self::assertFalse($this->pdo()->inTransaction());
    }

    public function testSingleRowClampLeavesGlobalRowUnchanged(): void
    {
        $id = $this->fixture->insertGlobal(1);

        $result = $this->manager->moveWithinScope($this->pdo(), $this->globalConfig(), null, $id, 999);

        self::assertTrue($result);
        self::assertSame([$id => 1], $this->reader->globalOrdersById());
        self::assertFalse($this->pdo()->inTransaction());
    }

    public function testExistingGapsMoveUpwardDoesNotGloballyNormalize(): void
    {
        $ids = $this->insertGlobalOrders(1, 3, 5);

        $result = $this->manager->moveWithinScope($this->pdo(), $this->globalConfig(), null, $ids[2], 2);

        self::assertTrue($result);
        self::assertSame([$ids[0] => 1, $ids[1] => 4, $ids[2] => 2], $this->reader->globalOrdersById());
        self::assertFalse($this->pdo()->inTransaction());
    }

    public function testExistingGapsMoveDownwardDoesNotGloballyNormalize(): void
    {
        $ids = $this->insertGlobalOrders(1, 3, 5);

        $result = $this->manager->moveWithinScope($this->pdo(), $this->globalConfig(), null, $ids[0], 4);

        self::assertTrue($result);
        self::assertSame([$ids[0] => 4, $ids[1] => 2, $ids[2] => 5], $this->reader->globalOrdersById());
        self::assertFalse($this->pdo()->inTransaction());
    }

    public function testInvalidIdIsRejectedBeforeTransactionBegins(): void
    {
        $id = $this->fixture->insertGlobal(1);
        $before = $this->reader->globalOrdersById();

        try {
            $this->manager->moveWithinScope($this->pdo(), $this->globalConfig(), null, -1, 1);
            self::fail('Expected invalid ordering operation exception.');
        } catch (InvalidOrderingOperationException) {
            self::assertSame($before, $this->reader->globalOrdersById());
            self::assertFalse($this->pdo()->inTransaction());
            self::assertSame([$id => 1], $before);
        }
    }

    public function testZeroIdIsRejectedBeforeTransactionBegins(): void
    {
        $this->fixture->insertGlobal(1);
        $before = $this->reader->globalOrdersById();

        try {
            $this->manager->moveWithinScope($this->pdo(), $this->globalConfig(), null, 0, 1);
            self::fail('Expected invalid ordering operation exception.');
        } catch (InvalidOrderingOperationException) {
            self::assertSame($before, $this->reader->globalOrdersById());
            self::assertFalse($this->pdo()->inTransaction());
        }
    }

    public function testInvalidTargetOrderIsRejectedBeforeTransactionBegins(): void
    {
        $id = $this->fixture->insertGlobal(1);
        $before = $this->reader->globalOrdersById();

        try {
            $this->manager->moveWithinScope($this->pdo(), $this->globalConfig(), null, $id, -1);
            self::fail('Expected invalid ordering operation exception.');
        } catch (InvalidOrderingOperationException) {
            self::assertSame($before, $this->reader->globalOrdersById());
            self::assertFalse($this->pdo()->inTransaction());
        }
    }

    public function testZeroTargetOrderIsRejectedBeforeTransactionBegins(): void
    {
        $id = $this->fixture->insertGlobal(1);
        $before = $this->reader->globalOrdersById();

        try {
            $this->manager->moveWithinScope($this->pdo(), $this->globalConfig(), null, $id, 0);
            self::fail('Expected invalid ordering operation exception.');
        } catch (InvalidOrderingOperationException) {
            self::assertSame($before, $this->reader->globalOrdersById());
            self::assertFalse($this->pdo()->inTransaction());
        }
    }

    public function testGlobalConfigurationRejectsScopeValueBeforeTransactionBegins(): void
    {
        $id = $this->fixture->insertGlobal(1);
        $before = $this->reader->globalOrdersById();

        try {
            $this->manager->moveWithinScope($this->pdo(), $this->globalConfig(), 'unexpected-scope', $id, 1);
            self::fail('Expected invalid ordering operation exception.');
        } catch (InvalidOrderingOperationException) {
            self::assertSame($before, $this->reader->globalOrdersById());
            self::assertFalse($this->pdo()->inTransaction());
        }
    }

    /**
     * @return list<int>
     */
    private function insertGlobalOrders(int ...$orders): array
    {
        $ids = [];
        foreach ($orders as $order) {
            $ids[] = $this->fixture->insertGlobal($order);
        }

        return $ids;
    }

    private function globalConfig(): ScopedOrderingConfig
    {
        return new ScopedOrderingConfig(table: OrderingSchemaManager::GLOBAL_TABLE);
    }
}
