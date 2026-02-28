# Database Schema: Maatify/LanguageCore

The following tables are owned and managed by `Maatify/LanguageCore`.

## 1. languages

The canonical registry of supported languages.

| Column                 | Type           | Description                                    |
|:-----------------------|:---------------|:-----------------------------------------------|
| `id`                   | `INT UNSIGNED` | Primary Key.                                   |
| `name`                 | `VARCHAR(64)`  | Display Name (e.g., "English (US)").           |
| `code`                 | `VARCHAR(16)`  | Canonical BCP 47 Code (Unique).                |
| `is_active`            | `TINYINT(1)`   | Global activation switch.                      |
| `fallback_language_id` | `INT UNSIGNED` | Pointer to fallback language (Self-Ref FK).    |
| `created_at`           | `DATETIME`     | Creation timestamp.                            |
| `updated_at`           | `DATETIME`     | Last update timestamp.                         |

**Constraints:**
*   Unique Index on `code`.
*   Foreign Key on `fallback_language_id` references `languages(id)`.

## 2. language_settings

UI-specific configuration for languages.

| Column        | Type              | Description                                  |
|:--------------|:------------------|:---------------------------------------------|
| `language_id` | `INT UNSIGNED`    | Foreign Key to `languages(id)`. PK.          |
| `direction`   | `ENUM(ltr, rtl)`  | Text direction. Default `ltr`.               |
| `icon`        | `VARCHAR(255)`    | Path or URL to flag icon.                    |
| `sort_order`  | `INT`             | Display priority (Lower = Earlier).          |

**Constraints:**
*   Primary Key is `language_id`.
*   Foreign Key `language_id` references `languages(id)` (Cascade Delete).
