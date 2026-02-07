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

**Requirement:** Add `es-MX` (Mexican Spanish) with fallback to `es-ES` (Spain Spanish).

**Steps:**
1.  **Create Languages:**
    ```php
    $enId = $langService->createLanguage('English', 'en-US', ...);
    $esId = $langService->createLanguage('Spanish (Spain)', 'es-ES', ...);
    $mxId = $langService->createLanguage('Spanish (Mexico)', 'es-MX', ...);
    ```

2.  **Configure Fallback:**
    *   Set `es-MX` -> `es-ES`.
    *   **Note:** Only one level of fallback is supported.

3.  **Runtime Logic:**
    ```php
    $text = $readService->getValue('es-MX', 'client', 'auth', 'login');

    if ($text === null) {
        // Explicit application-level fallback to English
        $text = $readService->getValue('en-US', 'client', 'auth', 'login');
    }
    ```

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
