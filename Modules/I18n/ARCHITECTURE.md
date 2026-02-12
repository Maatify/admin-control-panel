# Architecture: Maatify/I18n

This document describes the architectural boundaries and components of the I18n module.

## 1. Database Schema

The module owns five tables that manage the translation layer.
It depends on `languages` (from LanguageCore) via Foreign Keys.

### `i18n_scopes`
*   **Purpose:** Top-level boundaries (e.g., `admin`, `client`).
*   **Columns:** `id`, `code`, `name`, `description`, `sort_order`, `is_active`.

### `i18n_domains`
*   **Purpose:** Functional areas (e.g., `auth`, `products`).
*   **Columns:** `id`, `code`, `name`, `description`, `is_active`.

### `i18n_domain_scopes`
*   **Purpose:** Governance Mapping (Scope <-> Domain).
*   **Columns:** `id`, `scope_code` (VARCHAR), `domain_code` (VARCHAR).
*   **Constraint:** Unique `(scope_code, domain_code)`.

### `i18n_keys`
*   **Purpose:** Registry of valid translation keys.
*   **Columns:** `id`, `scope` (VARCHAR), `domain` (VARCHAR), `key_part`, `description`, `created_at`.
*   **Constraint:** Unique `(scope, domain, key_part)`.

### `i18n_translations`
*   **Purpose:** Text values.
*   **Columns:** `id`, `language_id` (FK -> LanguageCore), `key_id` (FK -> i18n_keys), `translation_value`, `updated_at`.
*   **Constraint:** Unique `(language_id, key_id)`.

## 2. Service Layer

### `I18nGovernancePolicyService`
*   **Role:** The Gatekeeper.
*   **Responsibility:** Enforces that Scopes and Domains exist and are mapped before keys can be created.

### `TranslationWriteService`
*   **Role:** The Writer.
*   **Responsibility:**
    *   Creates/Renames Keys.
    *   Upserts Translations.
    *   Deletes Translations.
*   **Behavior:** Fail-Hard (Throws Exceptions).

### `TranslationReadService`
*   **Role:** The Reader (Single Value).
*   **Responsibility:** Fetches specific keys, handles fallback logic.
*   **Behavior:** Fail-Soft (Returns null).

### `TranslationDomainReadService`
*   **Role:** The Reader (Bulk).
*   **Responsibility:** Fetches entire domains for UI loading.
*   **Behavior:** Fail-Soft (Returns empty DTO).

## 3. Dependencies

*   **Internal:** `maatify/language-core` (for `languages` table and `LanguageRepository`).
*   **External:** PDO (Database).
