<?php

declare(strict_types=1);

namespace Maatify\Persistence\Tests\Unit\Pdo\Ordering;

use Maatify\Persistence\Exception\InvalidOrderingConfigurationException;
use Maatify\Persistence\Pdo\Ordering\ScopedOrderingConfig;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ScopedOrderingConfigTest extends TestCase
{
    public function testConstructorDefaultsToGlobalOrderingWithSoftDeleteFiltering(): void
    {
        $config = new ScopedOrderingConfig('items');

        self::assertSame('items', $config->table);
        self::assertNull($config->scopeColumn);
        self::assertSame('id', $config->idColumn);
        self::assertSame('display_order', $config->orderColumn);
        self::assertSame('deleted_at', $config->deletedAtColumn);
    }

    public function testGlobalConfigurationHasNoScopeColumn(): void
    {
        $config = new ScopedOrderingConfig('items');

        self::assertNull($config->quotedScopeColumn());
    }

    public function testScopedConfigurationReturnsQuotedScopeColumn(): void
    {
        $config = new ScopedOrderingConfig('items', 'tenant_id');

        self::assertSame('`tenant_id`', $config->quotedScopeColumn());
    }

    public function testCustomColumnsAreStoredAndQuoted(): void
    {
        $config = new ScopedOrderingConfig(
            table: 'items',
            scopeColumn: 'account_id',
            idColumn: 'item_id',
            orderColumn: 'sort_index',
            deletedAtColumn: 'removed_at',
        );

        self::assertSame('`item_id`', $config->quotedIdColumn());
        self::assertSame('`sort_index`', $config->quotedOrderColumn());
        self::assertSame('`account_id`', $config->quotedScopeColumn());
        self::assertSame('`removed_at`', $config->quotedDeletedAtColumn());
    }

    public function testDeletedAtColumnCanBeDisabled(): void
    {
        $config = new ScopedOrderingConfig('items', deletedAtColumn: null);

        self::assertNull($config->deletedAtColumn);
        self::assertNull($config->quotedDeletedAtColumn());
    }

    public function testQuotesNormalTable(): void
    {
        self::assertSame('`items`', (new ScopedOrderingConfig('items'))->quotedTable());
    }

    public function testQuotesSchemaQualifiedTable(): void
    {
        self::assertSame('`tenant_1`.`items`', (new ScopedOrderingConfig('tenant_1.items'))->quotedTable());
    }

    public function testQuotesIdColumn(): void
    {
        self::assertSame('`item_id`', (new ScopedOrderingConfig('items', idColumn: 'item_id'))->quotedIdColumn());
    }

    public function testQuotesOrderColumn(): void
    {
        self::assertSame('`sort_order`', (new ScopedOrderingConfig('items', orderColumn: 'sort_order'))->quotedOrderColumn());
    }

    public function testQuotesDeletedAtColumn(): void
    {
        self::assertSame('`removed_at`', (new ScopedOrderingConfig('items', deletedAtColumn: 'removed_at'))->quotedDeletedAtColumn());
    }

    /**
     * @param non-empty-string $table
     * @param non-empty-string $quoted
     */
    #[DataProvider('validTableProvider')]
    public function testAcceptsValidTableIdentifiers(string $table, string $quoted): void
    {
        self::assertSame($quoted, (new ScopedOrderingConfig($table))->quotedTable());
    }

    /** @return iterable<string, array{non-empty-string, non-empty-string}> */
    public static function validTableProvider(): iterable
    {
        yield 'starts with letter' => ['items', '`items`'];
        yield 'starts with underscore' => ['_items', '`_items`'];
        yield 'contains digits after first character' => ['items2', '`items2`'];
        yield 'valid schema.table' => ['tenant_1.items2', '`tenant_1`.`items2`'];
    }

    /** @param non-empty-string $column */
    #[DataProvider('validColumnProvider')]
    public function testAcceptsValidColumnIdentifiers(string $column): void
    {
        $config = new ScopedOrderingConfig('items', idColumn: $column, orderColumn: $column, deletedAtColumn: $column);

        self::assertSame('`' . $column . '`', $config->quotedIdColumn());
        self::assertSame('`' . $column . '`', $config->quotedOrderColumn());
        self::assertSame('`' . $column . '`', $config->quotedDeletedAtColumn());
    }

    /** @return iterable<string, array{non-empty-string}> */
    public static function validColumnProvider(): iterable
    {
        yield 'starts with letter' => ['column'];
        yield 'starts with underscore' => ['_column'];
        yield 'contains digits after first character' => ['column2'];
    }

    /** @param non-empty-string $table */
    #[DataProvider('invalidTableProvider')]
    public function testRejectsInvalidTableIdentifiers(string $table): void
    {
        $this->expectException(InvalidOrderingConfigurationException::class);

        new ScopedOrderingConfig($table);
    }

    /** @return iterable<string, array{non-empty-string}> */
    public static function invalidTableProvider(): iterable
    {
        yield 'starts with digit' => ['1items'];
        yield 'whitespace' => ['item list'];
        yield 'hyphen' => ['item-list'];
        yield 'backticks' => ['`items`'];
        yield 'more than one dot' => ['db.schema.items'];
        yield 'leading dot' => ['.items'];
        yield 'trailing dot' => ['items.'];
        yield 'sql fragment' => ['items WHERE 1=1'];
        yield 'sql comment' => ['items--comment'];
    }

    /** @param non-empty-string $column */
    #[DataProvider('invalidColumnProvider')]
    public function testRejectsInvalidCustomIdColumn(string $column): void
    {
        $this->expectException(InvalidOrderingConfigurationException::class);

        new ScopedOrderingConfig('items', idColumn: $column);
    }

    /** @param non-empty-string $column */
    #[DataProvider('invalidColumnProvider')]
    public function testRejectsInvalidOrderColumn(string $column): void
    {
        $this->expectException(InvalidOrderingConfigurationException::class);

        new ScopedOrderingConfig('items', orderColumn: $column);
    }

    /** @param non-empty-string $column */
    #[DataProvider('invalidColumnProvider')]
    public function testRejectsInvalidScopeColumn(string $column): void
    {
        $this->expectException(InvalidOrderingConfigurationException::class);

        new ScopedOrderingConfig('items', scopeColumn: $column);
    }

    /** @param non-empty-string $column */
    #[DataProvider('invalidColumnProvider')]
    public function testRejectsInvalidDeletedAtColumn(string $column): void
    {
        $this->expectException(InvalidOrderingConfigurationException::class);

        new ScopedOrderingConfig('items', deletedAtColumn: $column);
    }

    /** @return iterable<string, array{non-empty-string}> */
    public static function invalidColumnProvider(): iterable
    {
        yield 'starts with digit' => ['1column'];
        yield 'whitespace' => ['column name'];
        yield 'hyphen' => ['column-name'];
        yield 'backticks' => ['`column`'];
        yield 'column containing dot' => ['table.column'];
        yield 'sql fragment' => ['column IS NULL'];
        yield 'sql comment' => ['column--comment'];
    }
}
