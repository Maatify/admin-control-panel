# 08. Error Handling

This chapter catalogs the specific exceptions and error scenarios enforced by the library.

## 1. Fail-Hard Writes (Exceptions)

All write operations (Admin APIs, Setup Scripts) **must** enforce strict rules and data integrity. They throw explicitly typed exceptions that **must** be handled.

| Exception Class | Description | Typically Thrown By |
| :--- | :--- | :--- |
| `LanguageNotFoundException` | Language ID or Code does not exist. | All Services |
| `LanguageAlreadyExistsException` | Language code already taken. | `LanguageManagementService` |
| `LanguageCreateFailedException` | Database insertion failure. | `LanguageManagementService` |
| `TranslationKeyNotFoundException` | Key ID does not exist. | `TranslationWriteService` |
| `TranslationKeyAlreadyExistsException` | Key `scope.domain.key` already exists. | `TranslationWriteService` |
| `TranslationKeyCreateFailedException` | Database insertion failure. | `TranslationWriteService` |
| `ScopeNotAllowedException` | Scope is invalid or inactive. | `I18nGovernancePolicyService` |
| `DomainNotAllowedException` | Domain is invalid or inactive. | `I18nGovernancePolicyService` |
| `DomainScopeViolationException` | Domain is not mapped to Scope. | `I18nGovernancePolicyService` |
| `TranslationUpsertFailedException` | Translation insert/update failure. | `TranslationWriteService` |

### Handling Example

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
*   **Missing Translation:** Returns `null` (after attempting fallback).
*   **Invalid Domain/Scope:** Returns empty `TranslationDomainValuesDTO` (`[]`).

**Handling Nulls:**
Application code **must** be robust to `null` returns.

```php
$text = $readService->getValue(..., 'welcome');

// Option 1: Default String
echo $text ?? 'Welcome';

// Option 2: Fallback Logic
if ($text === null) {
    Logger::warning('Missing translation key: welcome');
    echo 'Welcome';
}
```

**Exception:**
`TranslationDomainReadService` **throws** `LanguageNotFoundException` if the `languageCode` is invalid. This signals a developer error, not a runtime data issue.
