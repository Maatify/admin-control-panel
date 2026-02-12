# 09. Real World Scenarios

This chapter provides end-to-end usage examples for common requirements.

## Scenario 1: Feature Expansion (Dark Mode)

**Requirement:** Add translation keys for a "Dark Mode" toggle in the User Dashboard (`client` scope).

**Steps:**
1.  **Check Governance:**
    Ensure `client` scope and `dashboard` domain exist and are mapped in `i18n_domain_scopes`.
    ```sql
    SELECT * FROM i18n_domain_scopes WHERE scope_code='client' AND domain_code='dashboard';
    ```

2.  **Create Keys:** (Write Service)
    ```php
    $writeService->createKey('client', 'dashboard', 'settings.dark_mode.label');
    $writeService->createKey('client', 'dashboard', 'settings.dark_mode.on');
    $writeService->createKey('client', 'dashboard', 'settings.dark_mode.off');
    ```

3.  **Add Translations:** (Write Service)
    ```php
    $writeService->upsertTranslation($enId, $keyId1, 'Dark Mode');
    $writeService->upsertTranslation($enId, $keyId2, 'On');
    $writeService->upsertTranslation($enId, $keyId3, 'Off');
    ```

4.  **Runtime Usage:** (Read Service)
    ```php
    $translations = $domainReadService->getDomainValues('en-US', 'client', 'dashboard');
    ```

## Scenario 2: Regional Fallback

**Requirement:** Add translations for `es-MX` (Mexican Spanish) which falls back to `es-ES` (Spain Spanish).

**Prerequisite:**
Languages `es-MX` and `es-ES` must be created and linked via `maatify/language-core`. This module simply consumes their codes.

**Steps:**
1.  **Add Translations:**
    Assume `es-ES` has all base translations. We only override specific keys for Mexico.

    ```php
    // Override the 'Welcome' message for Mexico
    $writeService->upsertTranslation(
        languageId: $mxId, // ID for es-MX
        keyId: $welcomeKeyId,
        value: '¡Bienvenido a México!'
    );
    // Other keys are left empty for es-MX
    ```

2.  **Runtime Logic:**
    When requesting a key for `es-MX`:
    ```php
    $text = $readService->getValue('es-MX', 'client', 'auth', 'login');
    ```
    *   If `es-MX` has a value, it is returned.
    *   If not, the service (via SQL join on `languages.fallback_language_id`) checks `es-ES`.
    *   If `es-ES` has a value, it is returned.
    *   If neither exists, `null` is returned.

## Scenario 3: Key Refactoring

**Requirement:** Rename `client.auth.btn_submit` ("Log In") to `client.auth.login.submit`.

**Steps:**
1.  **Find the Key ID:**
    ```sql
    SELECT id FROM i18n_keys WHERE key_part='btn_submit';
    -- Assume ID = 105
    ```

2.  **Rename:**
    ```php
    $writeService->renameKey(
        keyId: 105,
        scope: 'client',
        domain: 'auth',
        key: 'login.submit'
    );
    ```

3.  **Result:**
    *   ID `105` is preserved.
    *   All translations are retained.
    *   Old key `btn_submit` is removed.
    *   Runtime reads **must** use `login.submit`.
