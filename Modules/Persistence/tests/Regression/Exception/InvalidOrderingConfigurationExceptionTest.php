<?php

declare(strict_types=1);

namespace Maatify\Persistence\Tests\Regression\Exception;

use Maatify\Exceptions\Exception\System\SystemMaatifyException;
use Maatify\Persistence\Exception\InvalidOrderingConfigurationException;
use Maatify\Persistence\Exception\PersistenceException;
use Maatify\Persistence\Pdo\Ordering\ScopedOrderingConfig;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class InvalidOrderingConfigurationExceptionTest extends TestCase
{
    public function testImplementsPersistenceExceptionContract(): void
    {
        $exception = new InvalidOrderingConfigurationException('Invalid ordering configuration.');

        self::assertInstanceOf(PersistenceException::class, $exception);
    }

    public function testIsClassifiedAsSystemMaatifyException(): void
    {
        $exception = new InvalidOrderingConfigurationException('Invalid ordering configuration.');

        self::assertInstanceOf(SystemMaatifyException::class, $exception);
    }

    /**
     * @param 'table'|'scopeColumn'|'idColumn'|'orderColumn'|'deletedAtColumn' $argumentName
     * @param non-empty-string $value
     */
    #[DataProvider('invalidConfigurationProvider')]
    public function testInvalidConfigurationThrowsInvalidOrderingConfigurationException(string $argumentName, string $value): void
    {
        $this->expectException(InvalidOrderingConfigurationException::class);

        match ($argumentName) {
            'table' => new ScopedOrderingConfig($value),
            'scopeColumn' => new ScopedOrderingConfig('items', scopeColumn: $value),
            'idColumn' => new ScopedOrderingConfig('items', idColumn: $value),
            'orderColumn' => new ScopedOrderingConfig('items', orderColumn: $value),
            'deletedAtColumn' => new ScopedOrderingConfig('items', deletedAtColumn: $value),
        };
    }

    /** @param list<mixed> $arguments */
    #[DataProvider('emptyStringConfigurationProvider')]
    public function testEmptyStringConfigurationThrowsInvalidOrderingConfigurationException(array $arguments): void
    {
        $this->expectException(InvalidOrderingConfigurationException::class);

        $reflection = new ReflectionClass(ScopedOrderingConfig::class);
        $reflection->newInstanceArgs($arguments);
    }

    /** @return iterable<string, array{list<mixed>}> */
    public static function emptyStringConfigurationProvider(): iterable
    {
        yield 'empty table' => [['']];
        yield 'empty scope column' => [['items', '']];
        yield 'empty id column' => [['items', null, '']];
        yield 'empty order column' => [['items', null, 'id', '']];
        yield 'empty deleted-at column' => [['items', null, 'id', 'display_order', '']];
    }

    /** @return iterable<string, array{'table'|'scopeColumn'|'idColumn'|'orderColumn'|'deletedAtColumn', non-empty-string}> */
    public static function invalidConfigurationProvider(): iterable
    {
        yield 'invalid table' => ['table', 'items; DROP TABLE items'];
        yield 'invalid scope column' => ['scopeColumn', 'scope.id'];
        yield 'invalid id column' => ['idColumn', 'id column'];
        yield 'invalid order column' => ['orderColumn', 'display-order'];
        yield 'invalid deleted-at column' => ['deletedAtColumn', '`deleted_at`'];
    }
}
