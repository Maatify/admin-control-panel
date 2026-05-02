<?php

declare(strict_types=1);

namespace Maatify\Persistence\Pdo\Ordering;

use Maatify\Persistence\Exception\InvalidOrderingConfigurationException;

/**
 * Immutable configuration for scoped ordering operations.
 *
 * This class describes the table and columns used by ScopedOrderingManager.
 * It deliberately validates and quotes SQL identifiers because table/column
 * names cannot be bound as PDO parameters.
 *
 * Important:
 * - Values such as row ids, scope values, and order numbers are still bound
 *   through PDO parameters.
 * - Identifiers such as table and column names must be trusted application
 *   constants, never raw user input.
 *
 * Supported table identifier formats:
 * - table
 * - schema.table
 *
 * Supported column identifier format:
 * - column
 */
final readonly class ScopedOrderingConfig
{
    /**
     * @param non-empty-string      $table           Trusted table identifier, e.g. "maa_shipping_rates".
     * @param non-empty-string|null $scopeColumn     Optional scope column, e.g. "method_id".
     * @param non-empty-string      $idColumn        Primary key column. Defaults to "id".
     * @param non-empty-string      $orderColumn     Ordering column. Defaults to "display_order".
     * @param non-empty-string|null $deletedAtColumn Optional soft-delete column. Pass null for tables without soft delete.
     */
    public function __construct(
        public string $table,
        public ?string $scopeColumn = null,
        public string $idColumn = 'id',
        public string $orderColumn = 'display_order',
        public ?string $deletedAtColumn = 'deleted_at',
    ) {
        self::assertTableIdentifier($this->table);
        self::assertColumnIdentifier($this->idColumn);
        self::assertColumnIdentifier($this->orderColumn);

        if ($this->scopeColumn !== null) {
            self::assertColumnIdentifier($this->scopeColumn);
        }

        if ($this->deletedAtColumn !== null) {
            self::assertColumnIdentifier($this->deletedAtColumn);
        }
    }

    /**
     * Returns the safely quoted table identifier.
     *
     * Examples:
     * - maa_shipping_rates      => `maa_shipping_rates`
     * - tenant_1.shipping_rates => `tenant_1`.`shipping_rates`
     */
    public function quotedTable(): string
    {
        return self::quoteTableIdentifier($this->table);
    }

    /**
     * Returns the safely quoted primary key column.
     */
    public function quotedIdColumn(): string
    {
        return self::quoteColumnIdentifier($this->idColumn);
    }

    /**
     * Returns the safely quoted ordering column.
     */
    public function quotedOrderColumn(): string
    {
        return self::quoteColumnIdentifier($this->orderColumn);
    }

    /**
     * Returns the safely quoted scope column, or null for global ordering.
     */
    public function quotedScopeColumn(): ?string
    {
        return $this->scopeColumn === null
            ? null
            : self::quoteColumnIdentifier($this->scopeColumn);
    }

    /**
     * Returns the safely quoted soft-delete column, or null if soft-delete
     * filtering is disabled for this table.
     */
    public function quotedDeletedAtColumn(): ?string
    {
        return $this->deletedAtColumn === null
            ? null
            : self::quoteColumnIdentifier($this->deletedAtColumn);
    }

    /**
     * Validates a SQL column identifier.
     *
     * Allowed:
     * - starts with a letter or underscore
     * - followed by letters, numbers, or underscores
     *
     * Not allowed:
     * - dots
     * - spaces
     * - backticks
     * - SQL fragments
     *
     * @param non-empty-string $identifier
     */
    private static function assertColumnIdentifier(string $identifier): void
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $identifier)) {
            throw new InvalidOrderingConfigurationException(
                sprintf('Invalid SQL column identifier "%s".', $identifier)
            );
        }
    }

    /**
     * Validates a SQL table identifier.
     *
     * Allowed:
     * - table
     * - schema.table
     *
     * Not allowed:
     * - more than one dot
     * - spaces
     * - backticks
     * - SQL fragments
     *
     * @param non-empty-string $identifier
     */
    private static function assertTableIdentifier(string $identifier): void
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*(\.[A-Za-z_][A-Za-z0-9_]*)?$/', $identifier)) {
            throw new InvalidOrderingConfigurationException(
                sprintf('Invalid SQL table identifier "%s".', $identifier)
            );
        }
    }

    /**
     * Quotes a validated SQL column identifier with backticks.
     *
     * @param non-empty-string $identifier
     */
    private static function quoteColumnIdentifier(string $identifier): string
    {
        self::assertColumnIdentifier($identifier);

        return '`' . $identifier . '`';
    }

    /**
     * Quotes a validated SQL table identifier with backticks.
     *
     * Supports both:
     * - table
     * - schema.table
     *
     * @param non-empty-string $identifier
     */
    private static function quoteTableIdentifier(string $identifier): string
    {
        self::assertTableIdentifier($identifier);

        if (str_contains($identifier, '.')) {
            return implode(
                '.',
                array_map(
                    static fn (string $part): string => '`' . $part . '`',
                    explode('.', $identifier)
                )
            );
        }

        return '`' . $identifier . '`';
    }
}
