# EXCHANGE_RATES — Module Reference

**Package:** `maatify/exchange-rates`
**Namespace root:** `Maatify\ExchangeRates\`
**Standard:** MODULE_BUILDING_STANDARD v1
**PHPStan:** level max, zero suppressions

---

## Table of Contents

1. [Design Rules](#1-design-rules)
2. [Schema](#2-schema)
3. [Exceptions](#3-exceptions)
4. [Shared Layer](#4-shared-layer)
5. [Admin — Provider](#5-admin--provider)
6. [Admin — Rate](#6-admin--rate)
7. [Admin — RateHistory](#7-admin--ratehistory)
8. [Customer — Rate](#8-customer--rate)
9. [Bootstrap / DI](#9-bootstrap--di)
10. [Financial Rules](#10-financial-rules)
11. [Extension Guide](#11-extension-guide)

---

## 1. Design Rules

### Independence guarantee
This module has **zero FK dependencies on host tables**.

- `base_currency_code` and `target_currency_code` are plain `CHAR(3)` ISO 4217 strings.
- The host is responsible for validating that a code exists in its `currencies` table before passing it to this module.
- No JOIN on host tables anywhere in the module.

### Rate convention
```
rate = how many target units equal 1 base unit
```
| base | target | rate | meaning |
|---|---|---|---|
| USD | EGP | 48.7500000000 | 1 USD = 48.75 EGP |
| EUR | USD | 1.0820000000 | 1 EUR = 1.082 USD |
| KWD | USD | 3.2600000000 | 1 KWD = 3.26 USD |

### Soft delete policy
- `deleted_at DATETIME NULL` — `NULL` = active, `NOT NULL` = soft-deleted
- Soft-deleted rows are excluded from all query defaults
- Pass `columnFilters['deleted' => 1]` to the admin list to see deleted rows
- Hard delete is **not supported** — history integrity depends on `RESTRICT` FKs
- **Soft-deleted rates and providers cannot be reordered** — `updateDisplayOrder()` throws `ExchangeRatesNotFoundException` if the row is soft-deleted

### display_order — two scopes

| Table | Scope | Meaning |
|---|---|---|
| `maa_er_providers` | Global (no scope column) | Provider priority for customer `null`-provider queries — lower = higher priority |
| `maa_er_rates` | Scoped to `provider_id` | Rate ordering within each provider |

Both are auto-assigned on create via `ScopedOrderingManager`.
Neither appears in any `CreateCommand` or `UpdateCommand`.
Both are updated via dedicated `updateDisplayOrder()` service calls only.

### Customer provider priority (`providerId = null`)
When `CustomerRateQueryService` is called with `providerId = null`, the query orders by:
```sql
ORDER BY p.display_order ASC, r.display_order ASC
```
The provider with the **lowest `display_order`** that has an active, non-deleted rate for the requested pair is used. Deactivated or soft-deleted providers are excluded via `INNER JOIN`.

### Soft delete + unique pair
A soft-deleted `(base, target, provider_id)` triple **cannot be recreated** — the `UNIQUE KEY` is not released on soft delete. Use restore (`deleted_at = NULL`) or `updateRate()` on the existing row instead. This is intentional to preserve rate history integrity.

### Financial precision
- All rate values are `DECIMAL(24,10)` in MySQL
- PHP: always `string` — **never** cast to `float`
- All arithmetic: `bcmath` with explicit scale
- Validation regex before any `bcmath` call: `/^\d+(?:\.\d{1,10})?$/`

---

## 2. Schema

### Run order (mandatory)
```
schema/01_providers.sql   →  maa_er_providers
schema/02_rates.sql       →  maa_er_rates
schema/03_rate_history.sql →  maa_er_rate_history
```

### `maa_er_providers`

| Column | Type | Notes |
|---|---|---|
| `id` | INT UNSIGNED AUTO_INCREMENT | PK |
| `name` | VARCHAR(100) | Human-readable label |
| `code` | VARCHAR(50) UNIQUE | Uppercase key — immutable after creation |
| `description` | TEXT NULL | Optional notes |
| `is_active` | TINYINT(1) DEFAULT 1 | Enabled / disabled |
| `display_order` | INT UNSIGNED DEFAULT 0 | Global priority — auto-assigned on create |
| `created_at` | DATETIME DEFAULT NOW | |
| `updated_at` | DATETIME NULL ON UPDATE | |
| `deleted_at` | DATETIME NULL | Soft delete |

### `maa_er_rates`

| Column | Type | Notes |
|---|---|---|
| `id` | INT UNSIGNED AUTO_INCREMENT | PK |
| `provider_id` | INT UNSIGNED | FK → `maa_er_providers.id` RESTRICT |
| `base_currency_code` | CHAR(3) | ISO 4217 — host-validated |
| `target_currency_code` | CHAR(3) | ISO 4217 — host-validated |
| `rate` | DECIMAL(24,10) | PHP: string + bcmath |
| `is_active` | TINYINT(1) DEFAULT 1 | |
| `display_order` | INT UNSIGNED DEFAULT 0 | Scoped to `provider_id` — auto-assigned on create |
| `created_at` | DATETIME DEFAULT NOW | |
| `updated_at` | DATETIME NULL ON UPDATE | |
| `deleted_at` | DATETIME NULL | Soft delete — does NOT release the UNIQUE KEY |

Unique key: `(base_currency_code, target_currency_code, provider_id)`

### `maa_er_rate_history`

| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT UNSIGNED AUTO_INCREMENT | PK — BIGINT for high-frequency scenarios |
| `rate_id` | INT UNSIGNED | FK → `maa_er_rates.id` RESTRICT |
| `provider_id` | INT UNSIGNED | Denormalised — FK → `maa_er_providers.id` RESTRICT |
| `base_currency_code` | CHAR(3) | Denormalised snapshot |
| `target_currency_code` | CHAR(3) | Denormalised snapshot |
| `rate` | DECIMAL(24,10) | The value at this moment |
| `recorded_at` | DATETIME | Provider publish time — **set by application**, not auto |
| `created_at` | DATETIME DEFAULT NOW | When written to DB |

> `recorded_at` is intentionally **not** `DEFAULT CURRENT_TIMESTAMP` to support bulk backfill with exact provider timestamps.

---

## 3. Exceptions

All module exceptions implement `ExchangeRatesExceptionInterface extends \Throwable`.
All extend `\RuntimeException`.
Named constructors are **required** — never `new SomeException('...')` at the call site.

### Catch the entire module

```php
use Maatify\ExchangeRates\Exception\ExchangeRatesExceptionInterface;

try {
    $rateCommandService->create($command);
} catch (ExchangeRatesExceptionInterface $e) {
    // handles any module exception
}
```

### Exception reference

| Class | Named constructors | When thrown |
|---|---|---|
| `ExchangeRatesNotFoundException` | `withId(int)` `withCode(string)` `withPair(string, string)` `withRateId(int)` | Record not found by id, code, or pair; also thrown when trying to reorder a soft-deleted row |
| `ExchangeRatesInvalidArgumentException` | `emptyField(string)` `invalidId(string)` `invalidDecimal(string, string)` `invalidCurrencyCode(string, string)` `sameCurrencyPair()` `rateMustBePositive(string)` `invalidDatetime(string, string)` `fieldTooLong(string, int)` | Invalid value in any command or input |
| `ExchangeRatesCodeAlreadyExistsException` | `withCode(string)` `withPair(string, string, int)` | Duplicate provider code or duplicate pair+provider |
| `ExchangeRatesConflictException` | `providerIsDeleted(int)` `providerIsInactive(int)` `rateValueUnchanged(int)` | Business rule conflict |

---

## 4. Shared Layer

### `ScopedOrderingManager`
`Maatify\ExchangeRates\Shared\Infrastructure\Persistence\Support\ScopedOrderingManager`

Supports both **scoped** (per `provider_id`) and **global** (no scope) ordering.

| Method | Signature | Returns | Notes |
|---|---|---|---|
| `getNextPosition` | `(PDO, string $table, ?string $scopeCol, ?int $scopeVal): int` | `int` | `scopeCol = null` → global ordering |
| `moveWithinScope` | `(PDO, string $table, ?string $scopeCol, ?int $scopeVal, int $id, int $newOrder, int $currentOrder): bool` | `bool` | Clamps `1 ≤ newOrder ≤ MAX`. Shifts conflicting rows in a transaction. |

| Table | scopeCol | scopeVal |
|---|---|---|
| `maa_er_providers` | `null` | `null` |
| `maa_er_rates` | `'provider_id'` | `$providerId` |

### `RateHistoryWriter`
`Maatify\ExchangeRates\Shared\Infrastructure\Support\RateHistoryWriter`

Module-local SQL support builder. **Not a public service** — called only by `PdoRateCommandRepository`.

| Method | Signature | Notes |
|---|---|---|
| `write` | `(int $rateId, int $providerId, string $baseCode, string $targetCode, string $rate, ?string $recordedAt): int` | `recordedAt` defaults to `date('Y-m-d H:i:s')` |

---

## 5. Admin — Provider

### Commands

#### `CreateProviderCommand`
```php
new CreateProviderCommand(
    name:        'European Central Bank',  // string, max 100 chars
    code:        'ecb',                    // string → uppercased → 'ECB', max 50 chars — immutable
    description: 'Daily XML feed',         // ?string
)
```

#### `UpdateProviderCommand`
```php
new UpdateProviderCommand(
    id:          42,
    name:        'ECB Updated',
    description: null,
)
```
`code` is **not updatable** — it is the stable identity key.

### Interfaces

#### `ProviderCommandRepositoryInterface`
| Method | Returns | Notes |
|---|---|---|
| `create(CreateProviderCommand)` | `int` | Auto-assigns `display_order` (global scope) |
| `update(UpdateProviderCommand)` | `bool` | |
| `updateStatus(int $id, bool $isActive)` | `bool` | |
| `updateDisplayOrder(int $id, int $displayOrder)` | `bool` | Throws `ExchangeRatesNotFoundException` if soft-deleted |
| `softDelete(int $id)` | `bool` | |

#### `ProviderQueryRepositoryInterface`
| Method | Returns | Notes |
|---|---|---|
| `findById(int $id)` | `?ProviderDTO` | **Includes soft-deleted rows.** Returns `null` only when id does not exist. Caller must check `$dto->deletedAt`. |
| `findByCode(string $code)` | `?ProviderDTO` | Excludes soft-deleted |
| `list(int $page, int $perPage, ?string $globalSearch, array $columnFilters)` | `array` | See pagination shape |

### DTOs

#### `ProviderDTO`
```php
$dto->id            // int
$dto->name          // string
$dto->code          // string
$dto->description   // ?string
$dto->isActive      // bool
$dto->displayOrder  // int
$dto->createdAt     // string 'Y-m-d H:i:s'
$dto->updatedAt     // ?string
$dto->deletedAt     // ?string — null means active
```

#### `ProviderListItemDTO`
```php
$dto->id            // int
$dto->name          // string
$dto->code          // string
$dto->isActive      // bool
$dto->displayOrder  // int
$dto->createdAt     // string
```

### Services

#### `ProviderCommandService`
```php
// Constructor — single dependency
public function __construct(
    private readonly ProviderCommandRepositoryInterface $commandRepo,
) {}
```

| Method | Throws | Notes |
|---|---|---|
| `create(CreateProviderCommand): int` | `ExchangeRatesCodeAlreadyExistsException` | Returns new id |
| `update(UpdateProviderCommand): void` | `ExchangeRatesNotFoundException` | |
| `updateStatus(int $id, bool $isActive): void` | `ExchangeRatesNotFoundException` | |
| `updateDisplayOrder(int $id, int $displayOrder): void` | `ExchangeRatesNotFoundException` | |
| `softDelete(int $id): void` | `ExchangeRatesNotFoundException` | |

#### `ProviderQueryService`
| Method | Throws | Notes |
|---|---|---|
| `getById(int $id): ProviderDTO` | `ExchangeRatesNotFoundException` | |
| `getByCode(string $code): ProviderDTO` | `ExchangeRatesNotFoundException` | |
| `list(...): array` | — | |

### Column filters for `list()`

| Key | Type | Behaviour |
|---|---|---|
| `is_active` | `int` (0 or 1) | Filter by status |
| `deleted` | `int` (0 or 1) | `1` = show only deleted; `0` = exclude deleted (default) |

Global search: matches `name` and `code` via separate named placeholders (`:global_name`, `:global_code`).

---

## 6. Admin — Rate

### Commands

#### `CreateRateCommand`
```php
new CreateRateCommand(
    providerId:         42,
    baseCurrencyCode:   'usd',            // → 'USD'
    targetCurrencyCode: 'egp',            // → 'EGP'
    rate:               '48.7500000000',  // decimal string — validated
    recordedAt:         null,             // ?string 'Y-m-d H:i:s' — defaults to now()
)
```

`display_order` is **not** in this command — auto-assigned within `provider_id` scope.

#### `UpdateRateCommand`
```php
new UpdateRateCommand(
    id:         99,
    rate:       '49.2000000000',
    recordedAt: '2024-06-15 12:00:00',  // ?string — for backfill
)
```

### Interfaces

#### `RateCommandRepositoryInterface`
| Method | Returns | Notes |
|---|---|---|
| `create(CreateRateCommand)` | `int` | Inserts rate + appends new value to history. In transaction. |
| `updateRate(UpdateRateCommand)` | `bool` | Updates rate + appends new value to history. In transaction. |
| `updateStatus(int $id, bool $isActive)` | `bool` | |
| `updateDisplayOrder(int $id, int $displayOrder)` | `bool` | Throws `ExchangeRatesNotFoundException` if soft-deleted |
| `softDelete(int $id)` | `bool` | |

#### `RateQueryRepositoryInterface`
| Method | Returns | Notes |
|---|---|---|
| `findById(int $id)` | `?RateDTO` | JOINs provider name/code |
| `findRawById(int $id)` | `?array<string, mixed>` | Used internally by command repo |
| `list(...)` | `array` | |

### Services

#### `RateCommandService`
| Method | Throws | Notes |
|---|---|---|
| `create(CreateRateCommand): int` | `ExchangeRatesNotFoundException` `ExchangeRatesConflictException` `ExchangeRatesCodeAlreadyExistsException` | Guards: provider must exist, not deleted, active |
| `updateRate(UpdateRateCommand): void` | `ExchangeRatesNotFoundException` `ExchangeRatesConflictException` | Throws `rateValueUnchanged` if `bccomp == 0` |
| `updateStatus(int $id, bool $isActive): void` | `ExchangeRatesNotFoundException` | |
| `updateDisplayOrder(int $id, int $displayOrder): void` | `ExchangeRatesNotFoundException` | |
| `softDelete(int $id): void` | `ExchangeRatesNotFoundException` | |

### Column filters for `list()`

| Key | Type | Behaviour |
|---|---|---|
| `provider_id` | `int` | Filter by provider |
| `is_active` | `int` (0 or 1) | Filter by status |
| `base_currency_code` | `string` | Exact match (uppercased) |
| `deleted` | `int` (0 or 1) | Soft-delete filter (default: exclude deleted) |

Global search: matches `base_currency_code` and `target_currency_code` via `:global_base`, `:global_target`.

---

## 7. Admin — RateHistory

Read-only. Written automatically by `PdoRateCommandRepository`.

### `RateHistoryQueryService`

| Method | Throws | Notes |
|---|---|---|
| `listByRateId(int $rateId, int $page = 1, int $perPage = 20): array` | — | Ordered by `recorded_at DESC` |
| `listByPair(string, string, int, int, ?int): array` | — | All providers unless scoped |
| `findRateAt(string, string, string, ?int): ?string` | `ExchangeRatesInvalidArgumentException` | Validates `Y-m-d H:i:s` format |

### `RateHistoryListItemDTO`
```php
$dto->id                   // int
$dto->rateId               // int
$dto->providerId           // int
$dto->providerName         // string
$dto->baseCurrencyCode     // string
$dto->targetCurrencyCode   // string
$dto->rate                 // string
$dto->recordedAt           // string
$dto->createdAt            // string
```

---

## 8. Customer — Rate

Exposes **only active, non-deleted rates** from **active, non-deleted providers**.

### `CustomerRateQueryService` — primary public API

#### `currentRate()`
```php
$rate = $service->currentRate(
    baseCurrencyCode:   'USD',
    targetCurrencyCode: 'EGP',
    providerId:         null,  // null = first active provider by p.display_order ASC
);
// Returns: '48.7500000000' or null
```

#### `convert()`
```php
$egp = $service->convert(
    amount:             '100.00',
    baseCurrencyCode:   'USD',
    targetCurrencyCode: 'EGP',
    providerId:         null,
    scale:              2,
);
// Returns: '4875.00' or null
// Throws: ExchangeRatesInvalidArgumentException — if amount format is invalid
```

Uses `bcmul($amount, $rate, $scale)` — never float multiplication.

#### `activeRatesForBase()`
```php
$collection = $service->activeRatesForBase('USD', null);

foreach ($collection as $dto) {
    echo $dto->targetCurrencyCode;  // 'EGP', 'EUR' ...
    echo $dto->rate;                // '48.7500000000'
    echo $dto->providerId;          // internal use — not in JSON output
}
```

### `CustomerRateDTO`
```php
// Properties
$dto->baseCurrencyCode    // string
$dto->targetCurrencyCode  // string
$dto->rate                // string
$dto->providerId          // int — internal only

// jsonSerialize() output (providerId intentionally excluded)
{
  "base_currency_code":   "USD",
  "target_currency_code": "EGP",
  "rate":                 "48.7500000000"
}
```

---

## 9. Bootstrap / DI

```php
use Maatify\ExchangeRates\Bootstrap\ExchangeRatesBindings;
use DI\ContainerBuilder;

$builder = new ContainerBuilder();
$builder->addDefinitions([PDO::class => $pdo]);

ExchangeRatesBindings::register($builder);

$container = $builder->build();

$customerRateQueryService = $container->get(CustomerRateQueryService::class);
$rateCommandService       = $container->get(RateCommandService::class);
$providerCommandService   = $container->get(ProviderCommandService::class);
```

### Wiring summary

| Binding | Implementation | Dependencies |
|---|---|---|
| `ProviderCommandRepositoryInterface` | `PdoProviderCommandRepository` | `PDO`, `ScopedOrderingManager` |
| `ProviderQueryRepositoryInterface` | `PdoProviderQueryRepository` | `PDO` |
| `ProviderCommandService` | — | `ProviderCommandRepositoryInterface` |
| `ProviderQueryService` | — | `ProviderQueryRepositoryInterface` |
| `RateCommandRepositoryInterface` | `PdoRateCommandRepository` | `PDO`, `RateHistoryWriter`, `ScopedOrderingManager` |
| `RateQueryRepositoryInterface` | `PdoRateQueryRepository` | `PDO` |
| `RateCommandService` | — | `RateCommandRepositoryInterface`, `RateQueryRepositoryInterface`, `ProviderQueryRepositoryInterface` |
| `RateQueryService` | — | `RateQueryRepositoryInterface` |
| `RateHistoryQueryRepositoryInterface` | `PdoRateHistoryQueryRepository` | `PDO` |
| `RateHistoryQueryService` | — | `RateHistoryQueryRepositoryInterface` |
| `CustomerRateQueryRepositoryInterface` | `PdoCustomerRateQueryRepository` | `PDO` |
| `CustomerRateQueryService` | — | `CustomerRateQueryRepositoryInterface` |

---

## 10. Financial Rules

| Rule | Detail |
|---|---|
| Storage type | `DECIMAL(24,10)` in MySQL |
| PHP type | `string` — always |
| Arithmetic | `bcmath` — always |
| Scale for `convert()` | Caller-specified, default `2` |
| Scale for `bccomp` | `10` (full rate precision) |
| Validation regex | `/^\d+(?:\.\d{1,10})?$/` — must pass before any bcmath call |
| Float | **Never** |

---

## 11. Extension Guide

### Backfill historical rates from a provider feed

```php
foreach ($feedRows as $row) {
    $rateCommandService->create(
        new CreateRateCommand(
            providerId:         $providerId,
            baseCurrencyCode:   $row['base'],
            targetCurrencyCode: $row['target'],
            rate:               $row['rate'],
            recordedAt:         $row['published_at'],  // exact provider timestamp
        )
    );
}
```

### Update rate from a live feed (cron)

```php
$rateCommandService->updateRate(
    new UpdateRateCommand(
        id:         $rateId,
        rate:       $latestRate,
        recordedAt: date('Y-m-d H:i:s'),
    )
);
```

### Swap the rate repository (e.g. add Redis cache layer)

```php
$builder->addDefinitions([
    CustomerRateQueryRepositoryInterface::class => fn() =>
        new MyCachedRateRepository($redis, $pdo),
]);
```

---

*This reference covers the complete public API of `maatify/exchange-rates` v1.0.0.*
*For changes between versions, see `CHANGELOG.md`.*
