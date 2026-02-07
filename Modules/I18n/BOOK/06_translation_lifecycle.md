# 06. Translation Lifecycle

This chapter covers the complete lifecycle of managing translation keys and their values using the `TranslationWriteService`.

## 1. Creating Keys

Use `createKey` to define a new structured key. This enforces strict governance policies.

```php
// Define a new key for the 'auth' domain in 'client' scope
$keyId = $service->createKey(
    scope: 'client',
    domain: 'auth',
    key: 'login.title',
    description: 'Main heading on the login page'
);
```

**Validations:**
1.  **Scope** must exist and be active.
2.  **Domain** must exist and be active.
3.  **Mapping** must exist (`client` <-> `auth`).
4.  **Uniqueness:** The combination `(client, auth, login.title)` must be unique.

**Exceptions:**
*   `DomainScopeViolationException`
*   `TranslationKeyAlreadyExistsException`

## 2. Renaming Keys

Keys are often renamed as requirements change. The library supports renaming the `key_part` while preserving the ID and existing translations.

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
*   You cannot rename a key to one that already exists in the same Scope/Domain.
*   You *cannot* change the Scope or Domain of a key directly. This would break historical data integrity. To "move" a key, you must create a new one and migrate translations manually.

## 3. Managing Descriptions

Descriptions help translators understand context. They are purely metadata.

```php
$service->updateKeyDescription(
    keyId: $keyId,
    description: 'Updated context: This appears above the username field.'
);
```

## 4. Upserting Translations

We use "Upsert" (Update or Insert) for translation values.

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
*   If a translation row exists for `(languageId, keyId)`, it is updated.
*   If not, a new row is inserted.
*   The `updated_at` timestamp is refreshed automatically.

## 5. Deleting Translations

You can remove a specific translation without deleting the key.

```php
$service->deleteTranslation(
    languageId: 1,
    keyId: $keyId
);
```

**Note:** If you delete a Key (`i18n_keys`), all associated translations cascade delete automatically via foreign keys. However, the service currently exposes `deleteTranslation` for precise control. Deleting a KEY is a destructive operation usually reserved for database admins or specific cleanup scripts, not routine application flow.
