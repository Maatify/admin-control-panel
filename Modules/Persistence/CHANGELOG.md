# Changelog

All notable changes to `maatify/persistence` will be documented in this file.

The format is intentionally simple and follows release-style sections:
`Added`, `Changed`, `Fixed`, `Deprecated`, `Removed`, and `Security`.

## [Unreleased]

### Added

- Added PDO scoped ordering utilities:
  - `Maatify\Persistence\Pdo\Ordering\ScopedOrderingConfig`
  - `Maatify\Persistence\Pdo\Ordering\ScopedOrderingManager`
- Added support for global ordering across a full table.
- Added support for scoped ordering by a configurable scope column, such as `method_id`, `product_id`, or `category_id`.
- Added configurable SQL identifiers:
  - table name
  - scope column
  - primary key column
  - ordering column
  - optional soft-delete column
- Added SQL identifier validation for table and column names.
- Added safe SQL identifier quoting for supported identifier formats.
- Added optional soft-delete filtering via `deleted_at IS NULL`.
- Added `getNextPosition()` to calculate the next available ordering position.
- Added `moveWithinScope()` to reorder rows safely within a global or scoped ordering sequence.
- Added scope-level locking using `SELECT ... FOR UPDATE` during reorder operations.
- Added target-row order lookup inside the transaction instead of trusting caller-provided current order.
- Added clamping for `newOrder` to prevent large ordering gaps.
- Added `rowExistsInScope()` helper.
- Added package-level custom exceptions:
  - `Maatify\Persistence\Exception\PersistenceException`
  - `Maatify\Persistence\Exception\InvalidOrderingConfigurationException`
  - `Maatify\Persistence\Exception\InvalidOrderingOperationException`
  - `Maatify\Persistence\Exception\OrderingTransactionException`
- Added README documentation for installation, usage, design notes, and exception handling.

### Design Notes

- `ScopedOrderingManager` is intentionally implemented as a stateless service class, not as a static helper.
- `PDO` is passed per method call so the manager can be reused across repositories and connections.
- `moveWithinScope()` owns its transaction and must be called outside active PDO transactions.
- `getNextPosition()` does not start a transaction or lock the scope by itself; callers should use repository/application-level locking for concurrent inserts when needed.
