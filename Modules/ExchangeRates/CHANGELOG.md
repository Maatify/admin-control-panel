# Changelog — maatify/exchange-rates

All notable changes to this module will be documented here.

---

## [1.0.0] — Unreleased

### Added
- `maa_er_providers` table — rate data sources (ECB, Fixer, Manual, custom)
- `maa_er_rates` table — current rate per (pair, provider) with display_order
- `maa_er_rate_history` table — append-only archive, supports backfill via `recorded_at`
- `Admin\Provider` — full CRUD with soft delete and status toggle
- `Admin\Rate` — full CRUD with history archiving on every rate change
- `Admin\RateHistory` — paginated history by rate_id or pair, point-in-time lookup
- `Customer\Rate` — `currentRate()`, `convert()` (bcmath), `activeRatesForBase()`
- `ScopedOrderingManager` — display_order auto-assign and shift within provider scope
- `RateHistoryWriter` — shared module-local support builder for history inserts
- `ExchangeRatesBindings` — PSR-11 compatible DI bootstrap
- PHPStan level max, zero suppressions
