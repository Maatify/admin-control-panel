# 02. Core Concepts

This chapter defines the strictly enforced terminology and data models used by the `Modules/I18n` library.

## Language Identity vs. Settings

The library separates language identity from UI configuration into distinct, immutable entities.

### 1. Language Identity (`languages`)
This entity represents the canonical language reference.
*   **Attributes:**
    *   `id` (int): Internal primary key.
    *   `code` (string): Canonical BCP 47 code (e.g., `en-US`, `ar-EG`).
    *   `name` (string): Human-readable name (e.g., "English (US)").
    *   `is_active` (bool): Global switch to disable/enable the language.
    *   `fallback_language_id` (int|null): Pointer to another language for missing keys.

This identity is rarely modified once created.

### 2. Language Settings (`language_settings`)
This entity stores UI-specific configuration.
*   **Attributes:**
    *   `direction` (enum): Text direction, strictly `LTR` or `RTL`.
    *   `icon` (string): Path or URL to a flag/icon.
    *   `sort_order` (int): Display priority in lists.

Separating these concepts allows the kernel to operate on `Language Identity` without concern for UI attributes like icons or text direction.

## Structured Keys

A "Translation Key" is a structured tuple of three parts, enforced by the database schema (unique constraint on `scope, domain, key_part`).

```text
scope . domain . key_part
```

### 1. Scope
The high-level consumer or boundary of the translation.
*   **Examples:** `admin`, `client`, `system`, `api`, `email`.
*   **Constraint:** A translation key cannot exist unless its `scope` is defined in `i18n_scopes` and is active.

### 2. Domain
The functional area or feature set within a scope.
*   **Examples:** `auth`, `billing`, `products`, `errors`.
*   **Constraint:** A translation key cannot exist unless its `domain` is defined in `i18n_domains` and is mapped to the `scope`.

### 3. Key Part
The specific label or message identifier.
*   **Examples:** `login.title`, `form.email.label`, `error.required`.
*   **Format:** Typically uses dot-notation (e.g., `form.email.label`), but the library treats it as a single string unit.

### The Full Key
When requesting a translation, you **must** provide all three parts:

| Scope | Domain | Key Part | Full Key String |
| :--- | :--- | :--- | :--- |
| `admin` | `dashboard` | `welcome` | `admin.dashboard.welcome` |
| `client` | `auth` | `login.btn` | `client.auth.login.btn` |

This structure prevents naming collisions and ensures deterministic loading of translation subsets.
