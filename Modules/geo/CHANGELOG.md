# Changelog

All notable changes to the Geo module will be documented in this file.

---

## [1.1.0] — 2026-05-16

### Added
- `geo_countries.is_state_required` — TINYINT(1) NOT NULL DEFAULT 0
- `geo_countries.is_postcode_required` — TINYINT(1) NOT NULL DEFAULT 0
- `CountryDTO::$isStateRequired` / `$isPostcodeRequired` (default false, at end of constructor for BC)
- `CreateCountryCommand::$isStateRequired` / `$isPostcodeRequired` (default false)
- `UpdateCountryCommand::$isStateRequired` / `$isPostcodeRequired` (`?bool`; `null` preserves current DB value)
- Repository INSERT / UPDATE include both new columns

## [1.0.0] — 2026-05-05

### Added
- `geo_countries` table — id, code (ISO alpha-2), name, icon, is_active, display_order
- `geo_country_translations` table — localised country names per language_id
- `geo_cities` table — id, country_id, code, name, is_active, display_order (scoped per country)
- `geo_city_translations` table — localised city names per language_id
- `CountryDTO` / `CityDTO` — immutable read-models with COALESCE translation support
- `CountryTranslationDTO` / `CityTranslationDTO` — language-table-independent translation read-models
- `GeoQueryService` — public API for active country list, city list by country_id, city list by country_code, all with optional languageId
- `GeoCommandService` — full CRUD + status toggle + reorder + translation upsert/delete
- `PdoGeoQueryReader` — zero dependency on the `languages` table; language_id is a plain INT
- `PdoGeoCommandRepository` — auto display_order via `ScopedOrderingManager`
- `GeoBindings` — PSR-11 compatible DI bootstrap
- Full exception hierarchy: `GeoExceptionInterface`, `CountryNotFoundException`, `CityNotFoundException`, `CountryCodeAlreadyExistsException`, `GeoInvalidArgumentException`, `GeoPersistenceException`
- All commands are `final readonly` with constructor-level validation

