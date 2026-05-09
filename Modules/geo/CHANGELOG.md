# Changelog

All notable changes to the Geo module will be documented in this file.

---

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

