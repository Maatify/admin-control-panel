# 06. Translation Lifecycle

This chapter documents the lifecycle of translation keys and values managed by `TranslationWriteService`.

## 1. Creating Keys

The `createKey` method defines a new structured key, enforcing strict governance.

```php
// Define a new key for the 'auth' domain in 'client' scope
$keyId = $service->createKey(
    scope: 'client',
    domain: 'auth',
    key: 'login.title',
    description: 'Main heading on the login page'
);
```

**Mandatory Validations:**
1.  **Scope** must exist and be active.
2.  **Domain** must exist and be active.
3.  **Mapping** must exist (`client` <-> `auth`).
4.  **Uniqueness:** The combination `(client, auth, login.title)` must not already exist.

**Exceptions:**
*   `DomainScopeViolationException` (Governance failure)
*   `TranslationKeyAlreadyExistsException` (Duplicate key)

## 2. Renaming Keys

The `renameKey` method updates the `key_part` of an existing key, preserving its ID and translations.

```php
// Rename 'login.title' to 'login.header'
$service->renameKey(
    keyId: $keyId,
    scope: 'client',
    domain: 'auth',
    key: 'login.header'
);
```

**Constraints:**
*   The new key name must not already exist in the target Scope/Domain.
*   The Scope and Domain **cannot** be changed via this method. Key movement requires explicit re-creation and migration.

## 3. Managing Descriptions

Descriptions provide metadata for translators.

```php
$service->updateKeyDescription(
    keyId: $keyId,
    description: 'Updated context: This appears above the username field.'
);
```

## 4. Upserting Translations

The `upsertTranslation` method inserts or updates a translation value.

```php
// Set English Value
$service->upsertTranslation(
    languageId: 1, // en-US
    keyId: $keyId,
    value: 'Welcome Back'
);

// Update English Value (Overwrites previous)
$service->upsertTranslation(
    languageId: 1,
    keyId: $keyId,
    value: 'Please Log In'
);
```

**Behavior:**
*   If a row exists for `(languageId, keyId)`, it is updated.
*   If no row exists, a new row is inserted.
*   `updated_at` timestamp is refreshed.

## 5. Deleting Translations

The `deleteTranslation` method removes a specific translation value.

```php
$service->deleteTranslation(
    languageId: 1,
    keyId: $keyId
);
```

**Note:** Deleting a `TranslationKey` (via `i18n_keys` cascade) automatically removes all associated translations. This method allows targeted removal of a single language's value.
