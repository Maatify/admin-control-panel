# 02. Core Concepts

This chapter defines the strictly enforced terminology and data models used by the `maatify/i18n` library.

## Language Identity

This module depends on **[Maatify/LanguageCore](../../LanguageCore/BOOK/02_language_identity.md)** for language identity (Code, Name, ID).
It uses the `language_id` (int) as a Foreign Key in the `i18n_translations` table.

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

| Scope    | Domain      | Key Part    | Full Key String           |
|:---------|:------------|:------------|:--------------------------|
| `admin`  | `dashboard` | `welcome`   | `admin.dashboard.welcome` |
| `client` | `auth`      | `login.btn` | `client.auth.login.btn`   |

This structure prevents naming collisions and ensures deterministic loading of translation subsets.
