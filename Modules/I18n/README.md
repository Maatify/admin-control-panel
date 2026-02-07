# Modules/I18n

**Kernel-Grade Internationalization Library**

This library provides a robust, database-driven internationalization (I18n) system designed for strict governance, structured keys, and high-performance runtime reads. It separates language identity from UI concerns and enforces a strong policy model for translation keys.

---

## 1. Library Identity

*   **Database-Driven:** All languages, keys, and translations reside in the database. No filesystem arrays or JSON files.
*   **Governance-First:** Enforces structural rules (Scopes & Domains) to prevent key sprawl and ensure organization.
*   **Fail-Soft Reads:** Runtime translation lookups are designed to never crash the application, returning `null` or empty collections on failure.
*   **Strict Writes:** Administrative operations (creating keys, languages) fail hard with typed exceptions to maintain data integrity.

---

## 2. Core Concepts

### Language Identity vs. Settings
*   **Identity (`languages`):** The immutable core of a language (e.g., `en-US`). Used for foreign keys and logic.
*   **Settings (`language_settings`):** UI-specific attributes like Text Direction (`LTR`/`RTL`), Icons, and Sort Order.

### Structured Keys
Translation keys are **not** arbitrary strings. They are structured as:
`scope` + `domain` + `key_part`

*   **Scope:** The consumer of the translation (e.g., `admin`, `client`, `system`, `api`).
*   **Domain:** The functional area (e.g., `auth`, `products`, `checkout`, `errors`).
*   **Key Part:** The specific label (e.g., `login.title`, `error.required`).

### Governance
*   **Policy:** You cannot create a key for a Scope or Domain that doesn't exist.
*   **Mapping:** A Domain must be explicitly allowed for a Scope (e.g., `billing` domain is valid for `admin` scope but not `public` scope).

### Fallback
Languages can form a chain. If a translation is missing in `en-GB`, the system can automatically resolve it from `en-US` (if configured).

---

## 3. Architecture

The module follows a strict layered architecture:

*   **Contracts (`Contract/`):** Interfaces defining repositories.
*   **Services (`Service/`):** Business logic for Reads, Writes, and Governance.
*   **Infrastructure (`Infrastructure/Mysql/`):** PDO-based implementations of repositories.
*   **DTOs (`DTO/`):** Strictly typed Data Transfer Objects for all data exchange.
*   **Exceptions (`Exception/`):** Typed exceptions for every failure scenario.
*   **Enums (`Enum/`):** `I18nPolicyModeEnum`, `TextDirectionEnum`.

---

## 4. Database Schema

The system uses 7 tables to enforce its model.

### Identity & UI
1.  **`languages`**: Canonical list of languages (`code`, `name`, `is_active`, `fallback_language_id`).
2.  **`language_settings`**: UI configuration (`direction`, `icon`, `sort_order`).

### Governance
3.  **`i18n_scopes`**: Allowed scopes (e.g., `admin`, `web`).
4.  **`i18n_domains`**: Allowed domains (e.g., `auth`, `common`).
5.  **`i18n_domain_scopes`**: Many-to-Many policy linking Domains to Scopes.

### Data
6.  **`i18n_keys`**: The registry of valid keys. Unique constraint on `(scope, domain, key_part)`.
7.  **`i18n_translations`**: The actual text values. Unique constraint on `(language_id, key_id)`.

---

## 5. Read vs. Write Semantics

| Feature | Writes (Admin/Setup) | Reads (Runtime) |
| :--- | :--- | :--- |
| **Strategy** | **Fail-Hard** | **Fail-Soft** |
| **Exceptions** | Throws typed exceptions (`LanguageNotFoundException`, `DomainScopeViolationException`). | Returns `null` or empty DTOs. Exception only on invalid language input. |
| **Validation** | Strict validation of governance rules. | Minimal validation; optimized for speed. |
| **Output** | Void or ID (int). | Strictly typed DTOs or primitive strings. |

---

## 6. Governance Model

The `I18nGovernancePolicyService` controls write access.

### STRICT Mode (Default)
*   **Scope** must exist and be active.
*   **Domain** must exist and be active.
*   **Domain** must be mapped to the **Scope**.
*   **Violation:** Throws `DomainScopeViolationException` or `NotAllowedException`.

### PERMISSIVE Mode
*   **If Exists:** Checks if active (throws if inactive).
*   **If mapped:** Checks mapping (throws if invalid).
*   **Missing:** If Scope or Domain are not in the DB, it allows the operation (bypass).

---

## 7. Public API Overview

### `LanguageManagementService`
*   `createLanguage(...)`: Create new language.
*   `updateLanguageSettings(...)`: Update UI settings.
*   `setFallbackLanguage(...)`: Link languages.

### `TranslationWriteService`
*   `createKey(...)`: Create a new structured key (enforces governance).
*   `renameKey(...)`: Rename a key part.
*   `upsertTranslation(...)`: Insert or update a translation value.
*   `deleteTranslation(...)`: Remove a value.

### `TranslationReadService`
*   `getValue(...)`: Fetch a single translation string (resolves fallback).

### `TranslationDomainReadService`
*   `getDomainValues(...)`: Fetch all translations for a `scope` + `domain` (optimized).

### `I18nGovernancePolicyService`
*   `assertScopeAndDomainAllowed(...)`: Validate rules manually.

---

## 8. Non-Goals

*   **No Files:** This library does not read/write PHP array files or JSON.
*   **No Auto-Discovery:** It does not scan your code to "find" keys. Keys must be explicitly created.
*   **No UI Assets:** It provides the API; it does not generate JS bundles or CSS.

---

## 9. Integration

### Requirements
*   PHP 8.2+
*   PDO (MySQL)

### Quick Start
See [HOW_TO_USE.md](HOW_TO_USE.md) for detailed code examples, wiring instructions, and troubleshooting.
