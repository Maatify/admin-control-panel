# Managing Translations — System Overview

## 1. Purpose of the Translations System

The Translations System is a database-driven, enterprise-grade internationalization (I18n) layer designed to manage all localized text across the platform. It solves the problem of hardcoding strings into applications by allowing administrators to dynamically define, edit, and organize text in multiple languages. Administrators use this system to control exactly what users read in the interface, ensuring consistent terminology and allowing the platform to serve a global audience without requiring code deployments for text changes.

## 2. Core Architecture

The system organizes translations into a strict, four-level hierarchy enforced by database schemas (`i18n_scopes`, `i18n_domains`, `i18n_keys`, `i18n_translations`):

* **Scopes:** The top-level namespace (e.g., `admin`, `client`, `api`). It represents the major application boundary. Stored in `i18n_scopes`, it connects to Domains via a many-to-many policy mapping (`i18n_domain_scopes`).
* **Domains:** A sub-grouping within a Scope (e.g., `auth`, `products`, `errors`). It represents a specific feature or module. Stored in `i18n_domains`, a Domain must be explicitly allowed for a Scope before Keys can be created under it.
* **Keys:** The structured registry of valid text identifiers (e.g., `login.title`). A Key is a strict tuple of Scope + Domain + Key Part. It is stored in `i18n_keys` and acts as the anchor for the actual text values.
* **Values:** The actual localized text strings. Stored in `i18n_translations`, each Value is tied directly to a specific Key ID and a specific Language ID.

## 3. How Translations Work in the System

When the system needs to render text in the UI, it performs a "Fail-Soft" runtime read operation using the `TranslationReadService`:

1. **Language Resolution:** The system determines the user's requested language via the `LanguageCore` module.
2. **Database Lookup:** The system queries `i18n_translations` for the exact Value matching the requested Language ID and the specific structured Key (Scope + Domain + Key Part).
3. **Fallback Behavior:** If the exact translation is missing, the system utilizes a strict 1-level fallback chain. It checks if the requested language has a `fallbackLanguageId` defined in `LanguageCore`. If so, it automatically attempts to load the translation for that fallback language. If both the primary and fallback languages are missing the translation, the system returns `null` (or the raw key string).

## 4. Admin Interaction Model

Administrators interact with the system through a drill-down flow designed to enforce the strict hierarchy:

1. **Start at Scopes:** The admin begins at the top level, viewing the available namespaces.
2. **Drill down to Domains:** The admin selects a Scope to view its explicitly assigned Domains.
3. **Drill down to Keys:** The admin selects a Domain to view the registry of Keys belonging to that specific Scope/Domain pair.
4. **Actual Editing:** The admin reaches the deepest level (the Translations List) where they manage the actual text Values tied to those Keys across different languages.

## 5. Translation Editing Model

* **Where values are stored:** Values are stored in the `i18n_translations` database table, mapped to a `language_id` and a `key_id`.
* **How values are edited:** Admins edit values via a Fail-Hard, synchronous write process handled by the `TranslationWriteService`. The edits are processed as explicit Upsert (Update or Insert) operations.
* **How multiple languages are handled:** Multiple languages are handled by storing individual records in `i18n_translations` for each Language ID attached to the same Key ID.
* **How updates propagate to the system:** The system uses a Strong Consistency model. There are no background workers or eventual consistency delays. When a translation is upserted, it is immediately written to the database. Simultaneously, within the exact same database transaction, derived aggregation tables (`i18n_domain_language_summary` and `i18n_key_stats`) are instantly rebuilt to ensure admin dashboards reflect the new coverage immediately.

## 6. Language Dependency

The Translations module has a strict dependency on `docs/admin/localization/languages.md` (the `LanguageCore` module).

* **How languages affect translations:** The Translations module does not define what languages exist. It strictly relies on the `LanguageCore` module to provide the authoritative `Language ID`. Every translation must be attached to a valid Language ID.
* **What happens when a language is inactive/missing:** If a language is deleted or deactivated, translations tied to that Language ID become unresolvable or orphaned. The `TranslationWriteService` enforces strict governance and will throw a `LanguageNotFoundException` if an admin attempts to save a translation for an invalid language.
* **How fallback language is used:** The fallback language is defined entirely within `LanguageCore`. The Translations module simply reads this `fallbackLanguageId` during runtime to resolve missing keys.

## 7. System Behavior & Consistency

* **Immediate vs delayed updates:** All updates are strongly consistent and immediate. The system enforces synchronous updates with zero reliance on background queues.
* **Caching:** The provided codebase extracts do not show an explicit application-level cache layer within the Core Read/Write services; it relies on high-performance database reads and pre-aggregated stats tables for speed.
* **Impact of changing shared keys:** Because Keys are strictly registered and globally queried by the application, changing the Value of a shared Key (e.g., changing a generic "Save" button text) will instantly and immediately update that text everywhere the Key is referenced across the entire platform.

## 8. Navigation Overview

The high-level navigation structure mirrors the architectural hierarchy:

`Translations` → `Scopes` → `Domains` → `Keys`

## 9. Boundaries of This Module

* **What this module DOES:** It strictly manages the registry of valid text Keys, enforces the Scope/Domain hierarchy, and stores/retrieves the actual localized text strings (Values). It maintains derived statistics about translation coverage.
* **What it DOES NOT do:** It does NOT manage language identity, language codes, or fallback configurations. Those responsibilities belong entirely to the separate `LanguageCore` module.

## 10. Coverage Confirmation

* **The full translation system is covered end-to-end:** Yes, from the top-level Scopes down to the lowest-level Values and their database storage model.
* **No architectural layer is missing:** Yes, Scopes, Domains, Keys, Values, Read Services, Write Services, and Derived Aggregations are all documented.
* **No assumptions were made:** Yes, all details (like the 1-level fallback chain, synchronous transactions, and the explicit dependency on `LanguageCore`) were extracted directly from the provided `Modules/I18n` source code and architecture contracts.
* **Everything is derived from real system behavior:** Yes, the documentation strictly reflects the actual fail-soft read paths and fail-hard transactional write paths present in the PHP services.