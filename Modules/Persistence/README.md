# Maatify Persistence

[![Latest Version](https://img.shields.io/packagist/v/maatify/persistence.svg?style=for-the-badge)](https://packagist.org/packages/maatify/persistence)
[![PHP Version](https://img.shields.io/packagist/php-v/maatify/persistence.svg?style=for-the-badge)](https://packagist.org/packages/maatify/persistence)
[![License](https://img.shields.io/packagist/l/maatify/persistence.svg?style=for-the-badge)](LICENSE)

![PHPStan](https://img.shields.io/badge/PHPStan-Level%20Max-4E8CAE)

[![Changelog](https://img.shields.io/badge/Changelog-View-blue)](CHANGELOG.md)
[![Security](https://img.shields.io/badge/Security-Policy-important)](SECURITY.md)

![Monthly Downloads](https://img.shields.io/packagist/dm/maatify/persistence?label=Monthly%20Downloads&color=00A8E8)
![Total Downloads](https://img.shields.io/packagist/dt/maatify/persistence?label=Total%20Downloads&color=2AA9E0)

![Maatify Ecosystem](https://img.shields.io/badge/Maatify-Ecosystem-blueviolet?style=for-the-badge)

[![Install](https://img.shields.io/badge/Install-composer%20require%20maatify%2Fpersistence-blue?style=for-the-badge)](https://packagist.org/packages/maatify/persistence)

Reusable persistence-layer utilities for Maatify projects.

This package contains infrastructure-level helpers that are shared across repositories/modules, without coupling domain modules to each other.

## Installation

```bash
composer require maatify/persistence
````

## Requirements

* PHP `>= 8.2`
* PDO extension

## Namespace

```php
Maatify\Persistence
```

## Current Components

### PDO Ordering

The package currently provides a scoped ordering manager for tables that use integer ordering columns such as `display_order`.

```php
Maatify\Persistence\Pdo\Ordering\ScopedOrderingConfig;
Maatify\Persistence\Pdo\Ordering\ScopedOrderingManager;
```

It supports:

* Global ordering across a whole table.
* Scoped ordering, for example ordering records per `method_id`, `product_id`, `category_id`, etc.
* Optional soft-delete filtering via `deleted_at IS NULL`.
* Safe SQL identifier validation for table and column names.
* Self-owned transactions for reorder operations.
* Scope locking with `SELECT ... FOR UPDATE` during reorder operations.
* Custom package exceptions.

---

## Ordering Configuration

Use `ScopedOrderingConfig` to describe the table and columns used for ordering.

```php
use Maatify\Persistence\Pdo\Ordering\ScopedOrderingConfig;

$config = new ScopedOrderingConfig(
    table: 'maa_shipping_rates',
    scopeColumn: 'method_id',
    idColumn: 'id',
    orderColumn: 'display_order',
    deletedAtColumn: 'deleted_at',
);
```

### Constructor Arguments

| Argument          |      Type |         Default | Description                                                    |
| ----------------- | --------: | --------------: | -------------------------------------------------------------- |
| `table`           |  `string` |        required | Trusted table identifier. Supports `table` or `schema.table`.  |
| `scopeColumn`     | `?string` |          `null` | Optional scope column. Example: `method_id`.                   |
| `idColumn`        |  `string` |            `id` | Primary key column.                                            |
| `orderColumn`     |  `string` | `display_order` | Ordering column.                                               |
| `deletedAtColumn` | `?string` |    `deleted_at` | Soft-delete column. Use `null` for tables without soft delete. |

### Important

Table and column names are SQL identifiers and cannot be bound as PDO parameters.

`ScopedOrderingConfig` validates and quotes identifiers internally, but identifiers must still be trusted application constants, never raw user input.

Allowed table identifiers:

```text
table
schema.table
```

Allowed column identifiers:

```text
column_name
```

---

## Getting the Next Position

```php
use Maatify\Persistence\Pdo\Ordering\ScopedOrderingManager;

$ordering = new ScopedOrderingManager();

$nextPosition = $ordering->getNextPosition(
    pdo: $pdo,
    config: $config,
    scopeValue: 12,
);
```

For global ordering:

```php
$config = new ScopedOrderingConfig(
    table: 'maa_shipping_methods',
    scopeColumn: null,
    idColumn: 'id',
    orderColumn: 'display_order',
    deletedAtColumn: null,
);

$nextPosition = $ordering->getNextPosition(
    pdo: $pdo,
    config: $config,
    scopeValue: null,
);
```

### Concurrency Note

`getNextPosition()` does not start a transaction and does not lock the scope.

If used for concurrent inserts, call it from a caller-owned transaction with appropriate repository/application-level locking.

---

## Moving a Row Within Scope

```php
$success = $ordering->moveWithinScope(
    pdo: $pdo,
    config: $config,
    scopeValue: 12,
    id: 55,
    newOrder: 3,
);
```

### Behavior

`moveWithinScope()`:

1. Starts its own transaction.
2. Locks all rows in the configured ordering scope using `SELECT ... FOR UPDATE`.
3. Reads the target row's current order from the database.
4. Clamps `newOrder` to the current maximum position in the scope.
5. Shifts affected rows up or down.
6. Updates the target row to the final order.
7. Commits the transaction.

It does **not** trust a caller-provided current order.

### Return Values

|  Return | Meaning                                            |
| ------: | -------------------------------------------------- |
|  `true` | Move succeeded.                                    |
|  `true` | Row was already at the requested/clamped position. |
| `false` | Target row does not exist in the configured scope. |
| `false` | Target row could not be updated.                   |

### Transaction Ownership

`moveWithinScope()` owns its transaction.

It must be called outside active PDO transactions.

If an active transaction already exists, it throws:

```php
Maatify\Persistence\Exception\OrderingTransactionException
```

---

## Checking Row Existence

```php
$exists = $ordering->rowExistsInScope(
    pdo: $pdo,
    config: $config,
    scopeValue: 12,
    id: 55,
);
```

If `deletedAtColumn` is configured, soft-deleted rows are treated as non-existing.

---

## Examples

### Global Ordering

```php
use Maatify\Persistence\Pdo\Ordering\ScopedOrderingConfig;
use Maatify\Persistence\Pdo\Ordering\ScopedOrderingManager;

$config = new ScopedOrderingConfig(
    table: 'maa_shipping_methods',
    scopeColumn: null,
    idColumn: 'id',
    orderColumn: 'display_order',
    deletedAtColumn: null,
);

$ordering = new ScopedOrderingManager();

$next = $ordering->getNextPosition($pdo, $config);

$ordering->moveWithinScope(
    pdo: $pdo,
    config: $config,
    scopeValue: null,
    id: 5,
    newOrder: 1,
);
```

### Scoped Ordering

```php
use Maatify\Persistence\Pdo\Ordering\ScopedOrderingConfig;
use Maatify\Persistence\Pdo\Ordering\ScopedOrderingManager;

$config = new ScopedOrderingConfig(
    table: 'maa_shipping_rates',
    scopeColumn: 'method_id',
    idColumn: 'id',
    orderColumn: 'display_order',
    deletedAtColumn: 'deleted_at',
);

$ordering = new ScopedOrderingManager();

$next = $ordering->getNextPosition(
    pdo: $pdo,
    config: $config,
    scopeValue: 2,
);

$ordering->moveWithinScope(
    pdo: $pdo,
    config: $config,
    scopeValue: 2,
    id: 15,
    newOrder: 4,
);
```

---

## Exceptions

All package exceptions implement:

```php
Maatify\Persistence\Exception\PersistenceException
```

### Available Exceptions

| Exception                               | Meaning                                                                                         |
| --------------------------------------- | ----------------------------------------------------------------------------------------------- |
| `InvalidOrderingConfigurationException` | Invalid table/column identifier or unsafe ordering configuration.                               |
| `InvalidOrderingOperationException`     | Invalid runtime operation arguments, such as invalid id, invalid order, or invalid scope usage. |
| `OrderingTransactionException`          | `moveWithinScope()` was called while a PDO transaction was already active.                      |

Example:

```php
use Maatify\Persistence\Exception\PersistenceException;

try {
    $ordering->moveWithinScope(
        pdo: $pdo,
        config: $config,
        scopeValue: 2,
        id: 15,
        newOrder: 4,
    );
} catch (PersistenceException $e) {
    // Handle package-level persistence exception.
}
```

---

## Design Notes

### Why not static methods?

`ScopedOrderingManager` is intentionally a service class, not a static helper.

This makes it easier to:

* Inject through a container.
* Test in isolation.
* Replace or decorate later.
* Add logging or driver-specific behavior in the future.

### Why pass PDO per call?

The manager is stateless. Passing `PDO` per call allows the same manager instance to be reused with different connections.

---

## Development

Run static analysis:

```bash
composer analyse
```

Run tests:

```bash
composer test
```

## License

MIT
