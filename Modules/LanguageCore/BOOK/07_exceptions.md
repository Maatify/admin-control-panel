# 07. Exceptions

This chapter catalogs the specific exceptions thrown by `Maatify/LanguageCore`.

## Exception Hierarchy

All exceptions extend `Maatify\LanguageCore\Exception\LanguageCoreException`.

## List of Exceptions

| Exception Class                    | Description                                | HTTP Status Hint |
|:-----------------------------------|:-------------------------------------------|:-----------------|
| `LanguageNotFoundException`        | The requested Language ID does not exist.  | 404              |
| `LanguageAlreadyExistsException`   | The Language Code is already in use.       | 409              |
| `LanguageCreateFailedException`    | Database insertion failed (generic).       | 500              |
| `LanguageUpdateFailedException`    | Database update failed (generic).          | 500              |
| `LanguageInvalidFallbackException` | Circular fallback or invalid ID provided.  | 400              |

## Usage Example

```php
try {
    $service->createLanguage('English', 'en-US', ...);
} catch (LanguageAlreadyExistsException $e) {
    // Handle conflict
} catch (LanguageCoreException $e) {
    // Handle generic core error
}
```
