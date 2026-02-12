# 08. Service Contracts

This chapter outlines the public API of the `LanguageManagementService`.

## LanguageManagementService

The primary entry point for all language operations.

### Methods

#### 1. Creation
```php
public function createLanguage(
    string $name,
    string $code,
    TextDirectionEnum $direction,
    ?string $icon = null,
    bool $isActive = true,
    ?int $fallbackLanguageId = null
): int;
```

#### 2. Settings Update
```php
public function updateLanguageSettings(
    int $languageId,
    ?TextDirectionEnum $direction = null,
    ?string $icon = null
): void;
```

#### 3. Sort Order
```php
public function updateLanguageSortOrder(int $languageId, int $sortOrder): void;
```

#### 4. Activation
```php
public function setLanguageActive(int $languageId, bool $isActive): void;
```

#### 5. Fallback
```php
public function setFallbackLanguage(int $languageId, ?int $fallbackId): void;
```

#### 6. Retrieval
```php
public function getLanguageById(int $id): ?LanguageDTO;
public function getLanguageByCode(string $code): ?LanguageDTO;
public function listActive(): LanguageCollectionDTO;
public function listAll(): LanguageCollectionDTO;
```

## DTOs

### LanguageDTO
Represents a fully hydrated language entity.
*   `int $id`
*   `string $code`
*   `string $name`
*   `bool $isActive`
*   `?int $fallbackLanguageId`
*   `LanguageSettingsDTO $settings`

### LanguageSettingsDTO
Represents the presentation settings.
*   `TextDirectionEnum $direction`
*   `?string $icon`
*   `int $sortOrder`
