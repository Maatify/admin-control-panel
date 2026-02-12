# Maatify/I18n

**Kernel-Grade Translation Subsystem**

This library provides a robust, database-driven internationalization (I18n) system designed for strict governance, structured keys, and high-performance runtime reads. It handles the *translation* layer, depending on `maatify/language-core` for identity.

![Maatify.dev](https://www.maatify.dev/assets/img/img/maatify_logo_white.svg)

---

[![Version](https://img.shields.io/packagist/v/maatify/i18n?label=Version&color=4C1)](https://packagist.org/packages/maatify/i18n)
[![PHP](https://img.shields.io/packagist/php-v/maatify/i18n?label=PHP&color=777BB3)](https://packagist.org/packages/maatify/i18n)
![PHP Version](https://img.shields.io/badge/php-%3E%3D8.2-blue)

![Monthly Downloads](https://img.shields.io/packagist/dm/maatify/i18n?label=Monthly%20Downloads&color=00A8E8)
![Total Downloads](https://img.shields.io/packagist/dt/maatify/i18n?label=Total%20Downloads&color=2AA9E0)

![Stars](https://img.shields.io/github/stars/Maatify/i18n?label=Stars&color=FFD43B)
[![License](https://img.shields.io/github/license/Maatify/i18n?label=License&color=blueviolet)](LICENSE)
![Status](https://img.shields.io/badge/Status-Stable-success)
[![Code Quality](https://img.shields.io/codefactor/grade/github/Maatify/i18n/main?color=brightgreen)](https://www.codefactor.io/repository/github/Maatify/i18n)

![PHPStan](https://img.shields.io/badge/PHPStan-Level%20Max-4E8CAE)

---

## Documentation Contract

This README serves as a high-level identity summary and architectural contract.

**All authoritative usage rules, lifecycle definitions, and runtime behaviors are defined in:**
👉 [**Maatify/I18n/BOOK/**](./BOOK/INDEX.md) (The Usage Book)
👉 [**Maatify/I18n/HOW_TO_USE.md**](./HOW_TO_USE.md) (Integration Guide)

**Contract Rules:**
1.  **The Book is Authoritative:** If this README and the Book diverge, the Book is the source of truth.
2.  **Strict Governance:** Usage of this library implies adherence to the Governance Model defined in `BOOK/03_governance_model.md`.

---

## 1. Dependencies

*   **[maatify/language-core](../LanguageCore/README.md):** Provides the `languages` identity table and `LanguageManagementService`.

---

## 2. Core Concepts

### Structured Keys
Translation keys are enforced as tuples: `scope` + `domain` + `key_part`.
*   **Scope:** `admin`, `client`, `api`
*   **Domain:** `auth`, `products`, `errors`
*   **Key Part:** `login.title`

### Governance
*   **Policy:** A key cannot be created unless its Scope and Domain exist.
*   **Mapping:** A Domain must be explicitly allowed for a Scope in `i18n_domain_scopes`.

---

## 3. Architecture

The module adheres to a strict layered architecture:

*   **Services (`Service/`):** Business logic (Read, Write, Governance).
*   **Infrastructure (`Infrastructure/Mysql/`):** PDO-based repositories.
*   **DTOs (`DTO/`):** Strictly typed Data Transfer Objects.
*   **Exceptions (`Exception/`):** Typed exceptions for all failure scenarios.

For details, see [**ARCHITECTURE.md**](ARCHITECTURE.md).

---

## 4. Database Schema

The system relies on 5 mandatory tables (plus 2 from LanguageCore):

1.  **`i18n_scopes`**: Allowed scopes.
2.  **`i18n_domains`**: Allowed domains.
3.  **`i18n_domain_scopes`**: Many-to-Many policy mapping.
4.  **`i18n_keys`**: Registry of valid keys.
5.  **`i18n_translations`**: Text values (References `languages.id`).

---

## 5. Read vs. Write Semantics

| Feature        | Writes (Admin/Setup)           | Reads (Runtime)               |
|:---------------|:-------------------------------|:------------------------------|
| **Strategy**   | **Fail-Hard**                  | **Fail-Soft**                 |
| **Exceptions** | Throws typed exceptions.       | Returns `null` or empty DTOs. |
| **Validation** | Strict governance enforcement. | Minimal validation for speed. |
| **Output**     | Void or ID (int).              | Strictly typed DTOs.          |

---

## 6. Integration

### Quick Start
Refer to **[HOW_TO_USE.md](HOW_TO_USE.md)** for:
*   Wiring Services & Repositories
*   Handling Governance Exceptions
*   Fetching Translations
