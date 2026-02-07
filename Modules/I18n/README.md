# Maatify/I18n

**Kernel-Grade Internationalization Subsystem**

This library provides a robust, database-driven internationalization (I18n) system designed for strict governance, structured keys, and high-performance runtime reads. It separates language identity from UI concerns and enforces a strong policy model for translation keys.

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

[//]: # ([![Changelog]&#40;https://img.shields.io/badge/Changelog-View-blue&#41;]&#40;CHANGELOG.md&#41;)
[//]: # ([![Security]&#40;https://img.shields.io/badge/Security-Policy-important&#41;]&#40;SECURITY.md&#41;)


---

## Documentation Contract

This README serves as a high-level identity summary and architectural contract.

**All authoritative usage rules, lifecycle definitions, and runtime behaviors are defined in:**
👉 [**Maatify/I18n/BOOK/**](./BOOK/INDEX.md) (The Usage Book)
👉 [**Maatify/I18n/HOW_TO_USE.md**](./HOW_TO_USE.md) (Integration Guide)

**Contract Rules:**
1.  **The Book is Authoritative:** If this README and the Book diverge, the Book is the source of truth.
2.  **Illustrative Only:** Examples in this README are for architectural context. Real implementation code must follow `HOW_TO_USE.md`.
3.  **Strict Governance:** Usage of this library implies adherence to the Governance Model defined in `BOOK/03_governance_model.md`.

---

## 1. Library Identity

*   **Database-Driven:** All languages, keys, and translations reside in the database.
*   **Governance-First:** Enforces structural rules (Scopes & Domains) to prevent key sprawl.
*   **Fail-Soft Reads:** Runtime lookups **must never** crash the application; they return `null` on failure.
*   **Strict Writes:** Administrative operations **must** fail hard with typed exceptions to maintain data integrity.

---

## 2. Core Concepts

### Language Identity vs. Settings
*   **Identity (`languages`):** The immutable core (e.g., `en-US`). Used for foreign keys.
*   **Settings (`language_settings`):** UI-specific attributes (Direction, Icons, Sort Order).

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

*   **Contracts (`Contract/`):** Repository interfaces.
*   **Services (`Service/`):** Business logic (Read, Write, Governance).
*   **Infrastructure (`Infrastructure/Mysql/`):** PDO-based repositories.
*   **DTOs (`DTO/`):** Strictly typed Data Transfer Objects.
*   **Exceptions (`Exception/`):** Typed exceptions for all failure scenarios.

---

## 4. Database Schema

The system relies on 7 mandatory tables:

1.  **`languages`**: Canonical identity.
2.  **`language_settings`**: UI configuration.
3.  **`i18n_scopes`**: Allowed scopes.
4.  **`i18n_domains`**: Allowed domains.
5.  **`i18n_domain_scopes`**: Many-to-Many policy mapping.
6.  **`i18n_keys`**: Registry of valid keys.
7.  **`i18n_translations`**: Text values.

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

### Requirements
*   PHP 8.2+
*   PDO (MySQL)

### Quick Start
Refer to **[HOW_TO_USE.md](HOW_TO_USE.md)** for:
*   Wiring Services & Repositories
*   Creating Languages
*   Handling Governance Exceptions
*   Fetching Translations
