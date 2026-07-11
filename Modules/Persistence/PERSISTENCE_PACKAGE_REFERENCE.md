# Maatify Persistence - Package Reference

## Package Identity

* **Package Name**: `maatify/persistence`
* **Namespace**: `Maatify\Persistence`
* **Purpose**: Reusable persistence-layer utilities for Maatify projects.
* **Runtime Requirements**:
  * PHP >= 8.2
  * `ext-pdo`
* **Explicit Composer Dependencies**:
  * `maatify/exceptions` (`^1.0`)
* **Boundaries**: Framework-agnostic and host-agnostic. No HTTP API, no generic application repository, no ORM, no container bindings.

## Public API Inventory

### `Maatify\Persistence\Pdo\Ordering\ScopedOrderingConfig`
* **Status**: `final readonly class`
* **Constructor**:
  ```php
  public function __construct(
      public string $table,
      public ?string $scopeColumn = null,
      public string $idColumn = 'id',
      public string $orderColumn = 'display_order',
      public ?string $deletedAtColumn = 'deleted_at'
  )
  ```
* **Public Properties**:
  * `string $table`
  * `?string $scopeColumn`
  * `string $idColumn`
  * `string $orderColumn`
  * `?string $deletedAtColumn`
* **Public Methods**:
  * `public function quotedTable(): string`
  * `public function quotedScopeColumn(): ?string`
  * `public function quotedIdColumn(): string`
  * `public function quotedOrderColumn(): string`
  * `public function quotedDeletedAtColumn(): ?string`
* **Exceptions**: Throws `InvalidOrderingConfigurationException` on invalid identifiers.
* **Design rules**:
  * `scopeColumn === null` represents global ordering.
  * `scopeColumn !== null` represents scoped ordering.
  * `deletedAtColumn === null` disables soft-delete filtering.
  * Nullable quoted methods return `null` when their configured column is disabled.
  * `table`: Supports `table` or `schema.table` formats.
  * Identifiers must be application-trusted constants.

### `Maatify\Persistence\Pdo\Ordering\ScopedOrderingManager`
* **Status**: `final readonly class`
* **Constructor**: Stateless class with no explicitly declared constructor.
* **Public Methods**:
  * `public function getNextPosition(\PDO $pdo, ScopedOrderingConfig $config, int|string|null $scopeValue = null): int`
    * Returns `MAX(order) + 1` (calculated via `COALESCE(MAX(...), 0) + 1`).
    * Returns `1` for an empty applicable scope.
    * Ignores soft-deleted rows when soft-delete filtering is configured.
    * Does not begin a transaction.
    * Does not lock.
    * May throw `InvalidOrderingOperationException` for inconsistent scope usage.
    * External PDO/database throwables may propagate unchanged.
    * Concurrency note: Use caller-owned lock for concurrent inserts.
  * `public function rowExistsInScope(\PDO $pdo, ScopedOrderingConfig $config, int|string|null $scopeValue, int $id): bool`
    * Returns `false` for `id <= 0`.
    * Returns `false` when the row is absent from the requested scope.
    * Treats soft-deleted rows as absent when soft-delete filtering is configured.
    * May throw `InvalidOrderingOperationException` for inconsistent scope usage.
    * May propagate external PDO/database throwables unchanged.
  * `public function moveWithinScope(\PDO $pdo, ScopedOrderingConfig $config, int|string|null $scopeValue, int $id, int $newOrder): bool`
    * Rejects inconsistent scope usage (`InvalidOrderingOperationException`).
    * Throws `InvalidOrderingOperationException` for `id <= 0`.
    * Throws `InvalidOrderingOperationException` for `newOrder <= 0`.
    * Throws `OrderingTransactionException` when PDO already has an active transaction.
    * Owns its transaction.
    * Locks the applicable active scope.
    * Reads the target order inside the transaction.
    * Returns `false` if the target is missing.
    * Clamps above-maximum order to the applicable maximum.
    * Returns `true` for an already satisfied/clamped no-op.
    * Shifts only the affected range.
    * Does not globally normalize pre-existing gaps.
    * Returns `false` and rolls back if the final target update reports no affected row.
    * Rolls back an owned transaction when a throwable occurs after transaction startup.
    * Rethrows the same original throwable.
    * Does not universally wrap PDO failures as package exceptions.

## SQL and Trust Boundaries

* **Identifiers**: SQL identifiers (table, columns) are validated configuration, not user input. They cannot be PDO-bound. Supported formats are standard identifier naming rules and `schema.table`.
* **Values**: All runtime values (ids, scope values) use prepared statements and PDO parameter binding.
* **No Host Assumptions**: No host schema assumptions or ORM dependencies.

## Exception Architecture

All package exceptions implement `Maatify\Persistence\Exception\PersistenceException`.
Distinguish these package exceptions from propagated `\PDOException` or other `\Throwable`.

| Exception | Maatify Base Class | Error Code | Safety Behavior | Triggering Conditions |
| --------- | ------------------ | ---------- | --------------- | --------------------- |
| `PersistenceException` | `\Throwable` | N/A | N/A | Interface implemented by all package exceptions. |
| `InvalidOrderingConfigurationException` | `SystemMaatifyException` | `ErrorCodeEnum::MAATIFY_ERROR` | default | Invalid/unsafe trusted SQL configuration identifiers. |
| `InvalidOrderingOperationException` | `ValidationMaatifyException` | `ErrorCodeEnum::INVALID_ARGUMENT` | default | Invalid runtime id, new order, or scope usage. |
| `OrderingTransactionException` | `UnsupportedMaatifyException` | `ErrorCodeEnum::UNSUPPORTED_OPERATION` | `defaultIsSafe(): false` | `moveWithinScope()` called with active PDO transaction. |
### `Maatify\Persistence\Exception\PersistenceException`
* **Status**: `interface`
* **Extends**: `\Throwable`
* **Description**: It is the package marker interface.
* **Public Methods**: It has no package-declared methods.
* **Rules**: It marks package-defined exceptions only. External PDO or infrastructure throwables are not required to implement it.

### `Maatify\Persistence\Exception\InvalidOrderingConfigurationException`
* **Status**: `final class`
* **Extends**: `Maatify\Exceptions\Exception\System\SystemMaatifyException`
* **Implements**: `Maatify\Persistence\Exception\PersistenceException`
* **Package-Declared Protected Method**:
  ```php
  protected function defaultErrorCode(): ErrorCodeInterface
  ```
  * Returns: `ErrorCodeEnum::MAATIFY_ERROR`
* **Public Methods**: No package-declared public methods beyond the inherited shared exception API.
* **Triggering Conditions**: Invalid trusted SQL configuration identifiers.

### `Maatify\Persistence\Exception\InvalidOrderingOperationException`
* **Status**: `final class`
* **Extends**: `Maatify\Exceptions\Exception\Validation\ValidationMaatifyException`
* **Implements**: `Maatify\Persistence\Exception\PersistenceException`
* **Package-Declared Protected Method**:
  ```php
  protected function defaultErrorCode(): ErrorCodeInterface
  ```
  * Returns: `ErrorCodeEnum::INVALID_ARGUMENT`
* **Public Methods**: No package-declared public methods beyond the inherited shared exception API.
* **Triggering Conditions**:
  * Invalid movement id
  * Invalid new ordering value
  * Inconsistent scope usage

### `Maatify\Persistence\Exception\OrderingTransactionException`
* **Status**: `final class`
* **Extends**: `Maatify\Exceptions\Exception\Unsupported\UnsupportedMaatifyException`
* **Implements**: `Maatify\Persistence\Exception\PersistenceException`
* **Package-Declared Protected Methods**:
  ```php
  protected function defaultErrorCode(): ErrorCodeInterface
  ```
  * Returns: `ErrorCodeEnum::UNSUPPORTED_OPERATION`

  ```php
  protected function defaultIsSafe(): bool
  ```
  * Returns: `false`
* **Public Methods**: No package-declared public methods beyond the inherited shared exception API.
* **Triggering Conditions**: Thrown when `moveWithinScope()` is called while PDO already has an active transaction.

## Integration Requirements

* **Real MySQL Requirement**: Required for integration tests. No SQLite fallback.
* **Environment Variables**:
  * `PERSISTENCE_TEST_MYSQL_DSN`
  * `PERSISTENCE_TEST_MYSQL_USER`
  * `PERSISTENCE_TEST_MYSQL_PASSWORD`
* **Test Database Isolation**: Assumes isolated test tables and requires local package privileges (trigger/table cleanup). Tests include trigger failure injection.
* **Current CI MySQL Baseline**: 8.4.10.

## Verification Model

The test categories are:
* Unit
* Regression
* Integration (requires MySQL, tests rollback/failure injection)
* PHP compatibility matrices
* Lowest dependency compatibility
* CI Gate

## Non-goals

* No ORM
* No framework bindings
* No Host application container bindings
* No Host tables
* No HTTP layer
* No generic application repository abstraction
* No implicit transaction participation for `moveWithinScope()`
