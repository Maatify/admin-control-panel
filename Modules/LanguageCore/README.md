# Maatify/LanguageCore

**Kernel-Grade Language Identity Subsystem**

This library provides the canonical source of truth for language identity, settings, and fallback logic within the Maatify ecosystem. It is designed to be a pure identity layer, completely decoupled from translation keys and values.

---

## 1. Philosophy

*   **Identity First:** Languages are treated as immutable identity records (e.g., `en-US`), distinct from the mutable translations associated with them.
*   **Translation-Agnostic:** This module knows nothing about translation keys, scopes, or domains. It only manages *which* languages exist and *how* they behave (direction, sort order, fallbacks).
*   **Kernel-Grade:** Designed as a low-level dependency for higher-level modules like `maatify/i18n`.

---

## 2. Core Responsibilities

*   **Identity:** Managing the `languages` table (ISO codes, names).
*   **Settings:** Managing the `language_settings` table (Direction `LTR/RTL`, Icons, Sort Order).
*   **Lifecycle:** Activation, deactivation, and fallback chaining.

---

## 3. Usage

This module is primarily used as a dependency for:
*   [**maatify/i18n**](../I18n/README.md) - The Translation Subsystem.

However, it can be used standalone for systems that need language awareness without full translation management (e.g., a simple region selector).

---

## 4. Documentation

Complete documentation is available in the **[Usage Book](BOOK/01_overview.md)**.

*   [**Overview**](BOOK/01_overview.md)
*   [**Language Identity**](BOOK/02_language_identity.md)
*   [**Settings & UI**](BOOK/03_language_settings.md)
*   [**Lifecycle Management**](BOOK/04_language_lifecycle.md)
*   [**Fallback Logic**](BOOK/05_fallback_logic.md)
*   [**Service Contracts**](BOOK/08_service_contracts.md)
