# 02. Core Concepts

This chapter defines the strict terminology and mental models used by the library.

## Language Identity vs. Settings

In many systems, a "language" is just a code like `en-US`. In this library, the concept of a "Language" is split into two distinct entities:

### 1. Language Identity (`languages`)
The immutable core. This represents the language as a foreign key for business logic and relationships.
*   **Attributes:**
    *   `id` (int): Internal primary key.
    *   `code` (string): Canonical BCP 47 code (e.g., `en-US`, `ar-EG`).
    *   `name` (string): Human-readable name (e.g., "English (US)").
    *   `is_active` (bool): Global switch to disable/enable the language.
    *   `fallback_language_id` (int|null): Pointer to another language for missing keys.

This identity is stable. It is rarely modified once created.

### 2. Language Settings (`language_settings`)
The mutable UI configuration. This represents how the language *looks* and *behaves* in the frontend application.
*   **Attributes:**
    *   `direction` (enum): Text direction, strictly `LTR` or `RTL`.
    *   `icon` (string): Path or URL to a flag/icon.
    *   `sort_order` (int): Display priority in lists.

Separating these concepts allows the kernel to operate on `Language Identity` without caring about UI concerns like icons or text direction.

## Structured Keys

A "Translation Key" is the unique identifier for a piece of text. In this library, a key is **never** just a string. It is a structured tuple of three parts:

```text
scope . domain . key_part
```

### 1. Scope
The high-level consumer or boundary of the translation.
*   **Examples:** `admin`, `client`, `system`, `api`, `email`.
*   **Purpose:** Partitions the translation database. An `admin` key is never accidentally loaded in the `client` app.

### 2. Domain
The functional area or feature set within a scope.
*   **Examples:** `auth`, `billing`, `products`, `errors`.
*   **Purpose:** Groups related keys together. All `auth` keys (login, register, reset password) live in one domain.

### 3. Key Part
The specific label or message identifier.
*   **Examples:** `login.title`, `form.email.label`, `error.required`.
*   **Purpose:** Identifies the exact string. It can contain dots for further nesting (e.g., `form.email.label`), but the library treats it as a single string unit within the domain.

### The Full Key
When you request a translation in code, you must provide all three parts:

| Scope | Domain | Key Part | Full Key String |
| :--- | :--- | :--- | :--- |
| `admin` | `dashboard` | `welcome` | `admin.dashboard.welcome` |
| `client` | `auth` | `login.btn` | `client.auth.login.btn` |

This structure is enforced by the database schema (unique constraint on `scope, domain, key_part`) and the `TranslationWriteService`.
