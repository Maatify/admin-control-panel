# maatify/exchange-rates

Standalone exchange-rate module for the Maatify ecosystem.

## What it does

- Manages **rate providers** (ECB, Fixer.io, Manual, custom APIs) with global display_order priority
- Stores the **current rate** per currency pair + provider, with per-provider display_order
- Archives every rate change to an **append-only history** table, supporting backfill via `recorded_at`
- Exposes a **customer API** for rate lookup and `bcmath` conversion, with automatic provider status guard

## What it does NOT do

- No FK to any host table (`currencies`, `languages`, `users` …)
- Currency codes are ISO 4217 `CHAR(3)` strings — validated by the host application
- No ORM, no query builder — plain PDO throughout
- No `float` arithmetic — all rates are `string` + `bcmath`

## Installation

```bash
composer require maatify/exchange-rates
```

Run schema files in order:

```sql
SOURCE schema/01_providers.sql;
SOURCE schema/02_rates.sql;
SOURCE schema/03_rate_history.sql;
```

Register bindings in your DI container:

```php
use Maatify\ExchangeRates\Bootstrap\ExchangeRatesBindings;

ExchangeRatesBindings::register($builder);
```

## Rate convention

```
rate = how many target units equal 1 base unit
```

| base | target | rate | meaning |
|---|---|---|---|
| USD | EGP | 48.7500000000 | 1 USD = 48.75 EGP |
| EUR | USD | 1.0820000000  | 1 EUR = 1.082 USD |

## Quick examples

### Customer API

```php
// Current rate — null = first active provider by display_order
$rate = $customerRateQueryService->currentRate('USD', 'EGP');
// '48.7500000000' or null

// Convert — bcmath, 2 decimal places
$egp = $customerRateQueryService->convert('100.00', 'USD', 'EGP', null, 2);
// '4875.00' or null

// All active rates for a base currency
$rates = $customerRateQueryService->activeRatesForBase('USD');
```

### Admin — Provider

```php
$id = $providerCommandService->create(
    new CreateProviderCommand(
        name:        'European Central Bank',
        code:        'ECB',
        description: 'Daily XML feed at ecb.europa.eu',
    )
);

$providerCommandService->updateStatus($id, isActive: false);
$providerCommandService->updateDisplayOrder($id, 1);
$providerCommandService->softDelete($id);
```

### Admin — Rate

```php
$rateId = $rateCommandService->create(
    new CreateRateCommand(
        providerId:         $id,
        baseCurrencyCode:   'USD',
        targetCurrencyCode: 'EGP',
        rate:               '48.7500000000',
    )
);

// Update rate — new value appended to history with recorded_at
$rateCommandService->updateRate(
    new UpdateRateCommand(id: $rateId, rate: '49.2000000000')
);

$rateCommandService->updateDisplayOrder($rateId, 2);
$rateCommandService->updateStatus($rateId, isActive: false);
$rateCommandService->softDelete($rateId);
```

### Admin — Rate History

```php
// Last 30 entries for a pair
$history = $rateHistoryQueryService->listByPair('USD', 'EGP', page: 1, perPage: 30);

// Point-in-time lookup
$rate = $rateHistoryQueryService->findRateAt('USD', 'EGP', '2024-06-01 00:00:00');
```

## Module structure

```
Modules/ExchangeRates/
├── LICENSE
├── README.md
├── CHANGELOG.md
├── EXCHANGE_RATES_MODULE_REFERENCE.md
├── composer.json
├── phpstan.neon
├── schema/
│   ├── 01_providers.sql
│   ├── 02_rates.sql
│   └── 03_rate_history.sql
└── src/
    ├── Bootstrap/
    │   └── ExchangeRatesBindings.php
    ├── Exception/
    │   ├── ExchangeRatesExceptionInterface.php
    │   ├── ExchangeRatesNotFoundException.php
    │   ├── ExchangeRatesInvalidArgumentException.php
    │   ├── ExchangeRatesCodeAlreadyExistsException.php
    │   └── ExchangeRatesConflictException.php
    ├── Shared/Infrastructure/
    │   ├── Persistence/Support/ScopedOrderingManager.php
    │   └── Support/RateHistoryWriter.php
    ├── Admin/
    │   ├── Provider/  (Command · Contract · DTO · Infrastructure · Service)
    │   ├── Rate/      (Command · Contract · DTO · Infrastructure · Service)
    │   └── RateHistory/ (Contract · DTO · Infrastructure · Service)
    └── Customer/
        └── Rate/     (Contract · DTO · Infrastructure · Service)
```

## Key design decisions

**Provider `display_order`** — determines which provider wins when the customer API is called with `providerId = null`. The provider with the lowest `display_order` that has an active, non-deleted rate for the requested pair is used.

**Soft delete + unique pair** — soft-deleted rates cannot be recreated with the same `(base, target, provider_id)` triple because the UNIQUE KEY is not released. Use restore (`deleted_at = NULL`) or `updateRate()` on the existing row instead.

**Customer provider guard** — the customer query JOINs `maa_er_providers` and filters `p.is_active = 1 AND p.deleted_at IS NULL`, so deactivating or soft-deleting a provider immediately hides its rates from the customer API.

**Append-only history** — `recorded_at` is set by the application, not `DEFAULT CURRENT_TIMESTAMP`, to support backfill imports from provider feeds with exact publish timestamps.

## License

MIT — see [LICENSE](LICENSE)
