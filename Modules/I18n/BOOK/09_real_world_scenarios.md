# 09. Real World Scenarios

This chapter demonstrates how to use the library to solve common problems.

## Scenario 1: Adding a New Feature

**Requirement:** You are adding a "Dark Mode" feature to the User Dashboard (`client` scope). You need to add keys for the toggle switch.

**Steps:**
1.  **Check Governance:** Ensure `client` scope and `dashboard` domain exist and are linked.
    ```sql
    -- Check i18n_domain_scopes
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
    // In your controller/template
    $translations = $domainReadService->getDomainValues('en-US', 'client', 'dashboard');
    ```

## Scenario 2: Handling Missing Translations

**Requirement:** A new language `es-MX` (Mexican Spanish) is added. Not all keys are translated yet. It should fall back to `es-ES` (Spain Spanish) if available, otherwise `en-US` (English).

**Steps:**
1.  **Create Languages:**
    ```php
    $enId = $langService->createLanguage('English', 'en-US', ...);
    $esId = $langService->createLanguage('Spanish (Spain)', 'es-ES', ...);
    $mxId = $langService->createLanguage('Spanish (Mexico)', 'es-MX', ...);
    ```
2.  **Configure Fallback:**
    *   Set `es-MX` -> `es-ES`.
    *   *Note: The library only supports one level of fallback.* If `es-ES` is missing the key, it will return `null`. You must handle the "English Default" in your application code or ensure `es-ES` is fully translated.

3.  **Runtime Logic:**
    ```php
    $text = $readService->getValue('es-MX', 'client', 'auth', 'login');

    if ($text === null) {
        // Fallback to English manually if 2nd level fails
        $text = $readService->getValue('en-US', 'client', 'auth', 'login');
    }
    ```

## Scenario 3: Renaming a Legacy Key

**Requirement:** You have a key `client.auth.btn_submit` ("Log In"). You want to rename it to `client.auth.login.submit` for better structure.

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
    *   The ID `105` remains the same.
    *   All translations linked to ID `105` are preserved.
    *   The old key `btn_submit` is gone.
    *   Runtime reads must now use `login.submit`.
