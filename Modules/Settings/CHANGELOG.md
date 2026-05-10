# Changelog

All notable changes to the Maatify Settings module are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
