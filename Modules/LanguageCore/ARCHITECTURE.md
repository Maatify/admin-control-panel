# Architecture: Maatify/LanguageCore

This document describes the architectural boundaries and components of the LanguageCore module.

## 1. Database Schema

The module owns two tables that form the kernel of language identity.

### `languages`
*   **Purpose:** Canonical identity.
*   **Columns:** `id`, `code` (unique), `name`, `is_active`, `fallback_language_id`.
*   **Nature:** Low churn, high referential integrity.

### `language_settings`
*   **Purpose:** UI-specific attributes.
*   **Columns:** `language_id` (FK), `direction` (enum), `icon`, `sort_order`.
*   **Nature:** Presentation layer configuration.

## 2. Service Layer

### `LanguageManagementService`
The primary entry point for all operations.
*   **Writes:** Create/Update languages, change settings, set fallbacks.
*   **Reads:** List active languages, resolve fallback chains.

## 3. Data Transfer Objects (DTOs)

All data acts strictly through DTOs to ensure type safety.

*   `LanguageDTO`: Represents a full language entity (Identity + Settings).
*   `LanguageSettingsDTO`: Represents just the settings portion.
*   `LanguageCollectionDTO`: A collection of language objects.

## 4. Enums

*   `TextDirectionEnum`: Strictly typed `LTR` or `RTL`.

## 5. Dependencies

*   **Internal:** None. This is a leaf module.
*   **External:** PDO (Database).
