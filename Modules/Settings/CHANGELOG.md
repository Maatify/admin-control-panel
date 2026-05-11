# Changelog

All notable changes to the Maatify Settings module are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.2.0] — 2026-05-11

### Added

- `SettingValueTypeProviderInterface` for pluggable, project-specific type validation
- `DefaultSettingValueTypeProvider` implementing the interface with built-in types
- Support for custom validation types without modifying the module
- `CUSTOM_TYPES.md` guide for implementing custom type providers
- DI container support for provider registration and injection

### Changed

- `AdminSettingService` now dependency-injects `SettingValueTypeProviderInterface` instead of hardcoding validation
- Type validation delegated to provider, enabling project-specific extensions
- Bootstrap `SettingsBindings` registers provider in DI container

### Benefits

- ✅ **Extensibility** — Projects can add email, URL, JSON, or custom types without forking
- ✅ **Separation of concerns** — validation logic isolated from service
- ✅ **DI-friendly** — provider is injected, easy to mock in tests
- ✅ **Configuration-based** — Support for dynamic type registration

---

## [1.1.0] — 2026-05-11

### Added

- `SettingValueType` enum for type-safe value type handling
- Automatic value validation based on type during updates
- Helper methods in enum: `label()`, `fromValue()`, `values()`, `all()`
- Better error messages showing human-readable type labels
- `ENUM_USAGE.md` guide for enum usage

### Changed

- Added internal `SettingValueType` enum for built-in provider validation helpers
- DTOs keep `string $valueType` to preserve custom type extensibility
- `AdminSettingService::updateValue()` now validates values against their types
- Exception messages include type labels (e.g., "Integer" instead of "int")

### Fixed

- Type validation is now enforced during updates
- No more silent type coercion failures

---

## [1.0.0] — 2026-05-11

### Added

- Initial release of Settings module
- Exception classes: `SettingsNotFoundException`, `SettingsInvalidArgumentException`
- DTO classes: `SettingDTO`, `SettingListItemDTO`, `SettingCollectionDTO`
- Admin repository interfaces and PDO implementations for command/query operations
- `AdminSettingService` for admin-level operations (CRUD on editable settings)
- `SettingValueService` for application runtime value retrieval with type casting
- `UpdateSettingValueCommand` for validated setting value updates
- Schema with `settings` table supporting: key, value, type, admin editability, notes
- Bootstrap/DI bindings for all services and repositories
- Support for multiple value types: bool, int, string, datetime, date
- Pagination with filtering and global search for admin list operations
- Default settings for: maintenance mode, default currency, default language, cart TTL

### Design Principles

- **Immutable structure** — `is_admin_editable=0` settings allow value-only updates
- **PDO-based** — no ORM, pure database access
- **Two-tier access** — admin service for configuration, value service for runtime reading
- **Type-safe getters** — dedicated methods for bool, int, string with optional defaults
- **PHPStan max** — zero type errors at strictest level

### Schema

- Table: `settings`
  - `id` (auto-increment primary key)
  - `setting_key` (unique business identifier)
  - `setting_value` (current value as string)
  - `value_type` (type hint: bool, int, string, datetime, date)
  - `is_admin_editable` (control field write access from admin UI)
  - `admin_note` (internal documentation)
  - `created_at`, `updated_at` (audit timestamps)
