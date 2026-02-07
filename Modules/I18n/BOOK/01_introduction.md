# 01. Introduction

## Library Identity

`Modules/I18n` is a kernel-grade internationalization library designed for enterprise applications that require strict governance, structured data, and high-performance runtime reads.

Unlike traditional i18n solutions that rely on filesystem arrays (PHP/JSON) or key-value storage, this library is **strictly database-driven**. It treats languages, keys, and translations as first-class relational entities.

## Design Philosophy

The library is built on four core pillars:

### 1. Governance-First
In large systems, translation keys often become a dumping ground for arbitrary strings. This library enforces a strict **Scope + Domain** policy. You cannot create a translation key unless it belongs to a pre-defined Scope and Domain, and that Domain is explicitly allowed for that Scope.

### 2. Structured Keys
Keys are not flat strings like `error_message_invalid_email`. They are structured hierarchical identifiers: `scope.domain.key_part`. This structure is enforced at the database level and by the `TranslationWriteService`.

### 3. Fail-Hard Writes / Fail-Soft Reads
*   **Writes (Admin/Setup):** Operations that modify the state (creating languages, keys, updating values) are designed to fail hard. If a policy is violated, a typed exception is thrown immediately. This ensures data integrity.
*   **Reads (Runtime):** Operations that fetch data for the end-user are designed to never crash the application. If a key is missing, a language is invalid, or a translation is not found, the service returns `null` or an empty object—never an exception.

### 4. Zero Implicit Magic
There is no "auto-discovery" of keys from your codebase. There is no "magic" fallback to filesystem files. All state exists explicitly in the database. If it's not in the database, it doesn't exist.

## What This Library Is NOT

*   **It is NOT a filesystem loader.** It does not read `.php` or `.json` files.
*   **It is NOT a frontend asset generator.** It provides the API to fetch translations, but it does not bundle them for JavaScript clients (though it supports endpoints to do so).
*   **It is NOT a drop-in replacement for Laravel/Symfony translators.** It uses its own services and contracts.

## Architecture at a Glance

*   **Infrastructure:** purely `PDO` based MySQL implementation.
*   **Services:** Separated into `Read`, `Write`, and `Governance` concerns.
*   **Data Transport:** All data is moved via strict DTOs (Data Transfer Objects). Arrays are rarely used.
