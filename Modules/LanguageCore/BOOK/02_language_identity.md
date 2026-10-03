# 02. Language Identity

This chapter defines the strict separation between "Identity" and "Settings" enforced by `Maatify/LanguageCore`.

## 1. Identity (`languages`)

The `languages` table represents the canonical, immutable identity of a language.

### Attributes
*   **ID (`id`):** Internal integer Primary Key. Used for all Foreign Keys in the system (e.g., `i18n_translations.language_id`).
*   **Code (`code`):** The BCP 47 canonical code (e.g., `en-US`, `ar-EG`, `zh-CN`). Must be unique across the system.
*   **Name (`name`):** The human-readable name (e.g., "English (US)").
*   **Status (`is_active`):** A global switch to enable or disable the language system-wide.

### Why Identity is Separate?
By isolating identity:
1.  **Stability:** The `id` and `code` rarely change. This allows caching and referencing without fear of UI changes breaking logic.
2.  **Performance:** Database joins on `INT` IDs are faster than strings.
3.  **Integrity:** Foreign Key constraints ensure no orphaned data (e.g., translations for a deleted language).

## 2. Immutable Core

Once created, the **Identity** attributes (Code) are effectively immutable. While the database allows updates, the system treats them as constant references.

The **Name** can be updated to correct typos, but it should not change the fundamental identity of the language record.

---

## 3. Reference Implementation

To retrieve identity information:

```php
$language = $service->getLanguageById(1);
echo $language->code; // "en-US"
```
