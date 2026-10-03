# 03. Language Settings

Language settings control the **Presentation Layer** of a language. These attributes live in the `language_settings` table.

## 1. Text Direction (`direction`)

The system enforces strict typing for text direction via `Maatify\LanguageCore\Enum\TextDirectionEnum`.

*   **LTR (Left-to-Right):** English, French, Spanish, etc.
*   **RTL (Right-to-Left):** Arabic, Hebrew, Persian, etc.

This setting is critical for UI rendering (e.g., flipping layouts, aligning text).

## 2. Icons (`icon`)

The `icon` field stores a path or URL to a flag or symbol representing the language.
*   **Type:** `VARCHAR(255)` (Nullable).
*   **Usage:** Displayed in language switchers.
*   **Example:** `assets/flags/us.svg`

## 3. Sort Order (`sort_order`)

The `sort_order` integer controls the display priority in lists.
*   **Logic:** Lower numbers appear first (Ascending sort).
*   **Default:** `0`.
*   **Usage:** Ensure primary languages (e.g., English) appear at the top of the selector.

## 4. Separation from Identity

These settings are **Mutable**.
Changing a flag icon or sort order does **not** affect the language's identity or break any foreign keys. This allows UI designers to tweak presentation without risking data integrity in the translation layer.
