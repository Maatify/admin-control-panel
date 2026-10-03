# 02. Language Identity

This chapter defines the strict separation between "Identity" and "Settings" enforced by `Maatify/LanguageCore`.

## 1. Identity (`languages`)

The `languages` table represents the canonical, immutable identity of a language.

### Attributes
*   **ID (`id`):** Internal integer Primary Key. Used for Foreign Keys inside LanguageCore (e.g., `language_settings.language_id`, `fallback_language_id`) and as Host navigation identity. It is **not** used by I18n: `maa_i18n_translations` references the language by its `code`.
*   **Code (`code`):** The BCP 47 canonical code (e.g., `en-US`, `ar-EG`, `zh-CN`). Must be unique across the system.
*   **Name (`name`):** The human-readable name (e.g., "English (US)").
*   **Status (`is_active`):** A global switch to enable or disable the language system-wide.

### Why Identity is Separate?
By isolating identity:
1.  **Stability:** The `id` and `code` rarely change. This allows caching and referencing without fear of UI changes breaking logic.
2.  **Performance:** Database joins on `INT` IDs are faster than strings.
3.  **Integrity:** Foreign Key constraints inside LanguageCore ensure no orphaned settings or fallback pointers. There is no FK from I18n translations to `languages`: translations are keyed by `code`, and what happens to them when a language is deleted or renamed is Host policy.

## 2. Immutable Core

Once created, the **Identity** attributes (Code) are effectively immutable. While the database allows updates, the system treats them as constant references.

The `code` is also the identity under which I18n stores translations. A code change is therefore a coordinated identity migration, never a plain update: a Host that stores translations under this code must change the LanguageCore code and re-key those translations in one transaction (`LanguageRepositoryInterface::getByIdForUpdate()` serializes the change). Codes are limited to 16 characters (the storage contract shared with I18n).

The **Name** can be updated to correct typos, but it should not change the fundamental identity of the language record.

---

## 3. Reference Implementation

To retrieve identity information:

```php
$language = $service->getLanguageById(1);
echo $language->code; // "en-US"
```
