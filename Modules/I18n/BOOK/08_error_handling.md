# 08. Error Handling

This chapter catalogs the specific exceptions and error scenarios you will encounter when working with the library.

## 1. Fail-Hard Writes (Exceptions)

All write operations (Admin APIs, Setup Scripts) are designed to enforce strict rules and data integrity. They throw explicitly typed exceptions that you **must** handle.

| Exception Class | Description | Typically Thrown By |
| :--- | :--- | :--- |
| `LanguageNotFoundException` | You tried to reference a language ID or Code that doesn't exist. | All Services |
| `LanguageAlreadyExistsException` | You tried to create a language with a `code` that is already taken. | `LanguageManagementService` |
| `LanguageCreateFailedException` | Database insertion failed (generic SQL error). | `LanguageManagementService` |
| `TranslationKeyNotFoundException` | You tried to update/delete/rename a key ID that doesn't exist. | `TranslationWriteService` |
| `TranslationKeyAlreadyExistsException` | You tried to create a key `scope.domain.key` that already exists. | `TranslationWriteService` |
| `TranslationKeyCreateFailedException` | Database insertion failed (generic SQL error). | `TranslationWriteService` |
| `ScopeNotAllowedException` | The `scope` provided is invalid or inactive. | `I18nGovernancePolicyService` |
| `DomainNotAllowedException` | The `domain` provided is invalid or inactive. | `I18nGovernancePolicyService` |
| `DomainScopeViolationException` | The `domain` exists but is not mapped to the `scope`. | `I18nGovernancePolicyService` |
| `TranslationUpsertFailedException` | Failed to insert/update a translation value. | `TranslationWriteService` |

### Try-Catch Example

```php
try {
    $writeService->createKey('admin', 'billing', 'invoice.title');
} catch (DomainScopeViolationException $e) {
    // Log: "Domain billing not allowed for admin scope"
    // Return 403 Forbidden
} catch (TranslationKeyAlreadyExistsException $e) {
    // Log: "Key invoice.title already exists"
    // Return 409 Conflict
} catch (Exception $e) {
    // Generic error (500)
}
```

## 2. Fail-Soft Reads (Nulls)

Runtime read operations (`TranslationReadService`) avoid exceptions to prevent application crashes.

*   **Missing Key:** Returns `null`.
*   **Missing Translation:** Returns `null` (after trying fallback).
*   **Invalid Domain/Scope:** Returns empty `TranslationDomainValuesDTO` (`[]`).

**Handling Nulls:**
Your application code must be prepared to handle `null`.

```php
$text = $readService->getValue(..., 'welcome');

// Option 1: Default String
echo $text ?? 'Welcome';

// Option 2: Fallback Logic
if ($text === null) {
    // Log missing key for later fix
    Logger::warning('Missing translation key: welcome');
    echo 'Welcome';
}
```

**Exception:**
`TranslationDomainReadService` *will* throw `LanguageNotFoundException` if the `languageCode` passed is invalid. This is considered a developer error (passing garbage), not a runtime data issue.
