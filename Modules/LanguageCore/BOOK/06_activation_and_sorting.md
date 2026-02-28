# 06. Activation and Sorting

This chapter details the mechanisms for controlling language visibility and order.

## 1. Global Activation (`is_active`)

The `is_active` flag is a master switch for the language.

### Purpose
*   **Maintenance:** Temporarily hide a language while translations are incomplete.
*   **Soft Deletion:** Disable a language without deleting historical data.

### Impact
*   **Lists:** `LanguageManagementService::listActive()` returns only where `is_active = 1`.
*   **Validation:** Consumers generally reject operations against inactive languages unless explicitly bypassed.

## 2. Sorting (`sort_order`)

The `sort_order` integer controls the visual presentation order in lists and selectors.

### Logic
*   **Ascending:** Lower numbers appear first.
*   **Default:** `0`.
*   **Stability:** If two languages have the same sort order, secondary sorting is typically by ID or Name (implementation specific).

### Managing Order
The service provides a method to update the order explicitly.

```php
// Move 'en-US' to position 1
$service->updateLanguageSortOrder($usId, 1);

// Move 'es-ES' to position 2
$service->updateLanguageSortOrder($esId, 2);
```

This updates the `language_settings` table.
