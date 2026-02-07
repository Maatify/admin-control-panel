# 01. Introduction

## Library Identity

`maatify/i18n` is a kernel-grade internationalization subsystem for enterprise applications requiring strict governance, structured data, and high-performance runtime reads.

The library operates exclusively as a **database-driven** solution. It treats languages, keys, and translations as relational entities with referential integrity. It does not support filesystem arrays (PHP/JSON) or key-value storage.

## Design Philosophy

The library implements four mandatory design pillars:

### 1. Governance-First
The system enforces a strict **Scope + Domain** policy. A translation key cannot be created unless it belongs to a pre-defined Scope and Domain, and that Domain is explicitly mapped to that Scope in the `i18n_domain_scopes` table.

### 2. Structured Keys
Keys are structured hierarchical tuples: `scope.domain.key_part`. This structure is enforced by the database schema (unique constraint) and by the `TranslationWriteService`. Flat keys (e.g., `error_message`) are prohibited.

### 3. Fail-Hard Writes / Fail-Soft Reads
*   **Writes (Admin/Setup):** State-modifying operations (creating languages, keys, updating values) **must** fail hard. Policy violations throw typed exceptions immediately to ensure data integrity.
*   **Reads (Runtime):** Data fetching operations **must** fail soft. Missing keys, invalid languages, or missing translations return `null` or empty DTOs. They do not throw exceptions, ensuring application stability.

### 4. Zero Implicit Magic
The library performs no auto-discovery or implicit loading. All state exists explicitly in the database. If a record is not in the database, it does not exist.

## Architectural Boundaries

*   **Infrastructure:** Implemented via `PDO` MySQL repositories.
*   **Services:** Strictly separated into `Read`, `Write`, and `Governance` responsibilities.
*   **Data Transport:** All data transfer occurs via strict DTOs. Arrays are not used for internal data passing.

## Non-Goals

*   **Filesystem Loading:** The library does not read `.php` or `.json` files.
*   **Frontend Asset Generation:** The library provides APIs to fetch translations but does not bundle them for clients.
*   **Framework Integration:** The library uses independent services and contracts, not framework-specific adapters.
