<?php

declare(strict_types=1);

namespace Maatify\Persistence\Tests\Integration\Pdo\Ordering;

use Maatify\Persistence\Exception\OrderingTransactionException;
use Maatify\Persistence\Pdo\Ordering\ScopedOrderingConfig;
use Maatify\Persistence\Pdo\Ordering\ScopedOrderingManager;
use Maatify\Persistence\Tests\Support\MySql\MySqlIntegrationTestCase;
use Maatify\Persistence\Tests\Support\MySql\OrderingSchemaManager;
use Maatify\Persistence\Tests\Support\MySql\OrderingStateReader;

final class ScopedOrderingManagerTransactionTest extends MySqlIntegrationTestCase
{
    private ScopedOrderingManager $manager;
    private OrderingStateReader $reader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = new ScopedOrderingManager();
        $this->reader = new OrderingStateReader($this->pdo());
    }

    public function testRejectsCallerOwnedTransactionAndLeavesItActive(): void
    {
        $first = $this->fixture->insertGlobal(1);
        $second = $this->fixture->insertGlobal(2);
        $before = $this->reader->globalOrdersById();

        $this->pdo()->beginTransaction();

        try {
            try {
                $this->manager->moveWithinScope($this->pdo(), $this->globalConfig(), null, $first, 2);
                self::fail('Expected ordering transaction exception.');
            } catch (OrderingTransactionException) {
                self::assertTrue($this->pdo()->inTransaction());
                self::assertSame($before, $this->reader->globalOrdersById());
                self::assertSame([$first => 1, $second => 2], $before);
            }
        } finally {
            if ($this->pdo()->inTransaction()) {
                $this->pdo()->rollBack();
            }
        }

        self::assertFalse($this->pdo()->inTransaction());
        self::assertSame($before, $this->reader->globalOrdersById());
    }

    public function testSuccessfulMovementClosesOwnedTransaction(): void
    {
        $ids = $this->insertGlobalOrders(1, 2, 3);

        $result = $this->manager->moveWithinScope($this->pdo(), $this->globalConfig(), null, $ids[0], 3);

        self::assertTrue($result);
        self::assertSame([$ids[0] => 3, $ids[1] => 1, $ids[2] => 2], $this->reader->globalOrdersById());
        self::assertFalse($this->pdo()->inTransaction());
    }

    public function testMissingTargetClosesOwnedTransaction(): void
    {
        $ids = $this->insertGlobalOrders(1, 2, 3);
        $before = $this->reader->globalOrdersById();
        $missingId = $ids[2] + 100;

        $result = $this->manager->moveWithinScope($this->pdo(), $this->globalConfig(), null, $missingId, 2);

        self::assertFalse($result);
        self::assertSame($before, $this->reader->globalOrdersById());
        self::assertFalse($this->pdo()->inTransaction());
    }

    public function testNoOpClosesOwnedTransaction(): void
    {
        $ids = $this->insertGlobalOrders(1, 2, 3);
        $before = $this->reader->globalOrdersById();

        $result = $this->manager->moveWithinScope($this->pdo(), $this->globalConfig(), null, $ids[2], 999);

        self::assertTrue($result);
        self::assertSame($before, $this->reader->globalOrdersById());
        self::assertFalse($this->pdo()->inTransaction());
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
