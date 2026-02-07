# 03. Governance Model

## Overview

The `Modules/I18n` library employs a strict governance model to prevent translation key sprawl and ensure data integrity.

This model is enforced by the **`I18nGovernancePolicyService`** during write operations. Runtime reads (fail-soft) do not throw governance exceptions, but will return empty/null results if governance rules are violated.

## 1. Scopes (`i18n_scopes`)

Scopes define the top-level boundaries of your application.

*   **Definition:** A unique `code` (e.g., `admin`, `web`) and display `name`.
*   **Purpose:** Ensures that translations intended for one context (e.g., backend emails) are not accidentally leaked or loaded into another context (e.g., frontend SPA).
*   **Enforcement:** You cannot create a translation key unless the `scope` exists in this table and is `is_active=1`.

## 2. Domains (`i18n_domains`)

Domains represent functional areas or features within a scope.

*   **Definition:** A unique `code` (e.g., `auth`, `products`, `billing`) and display `name`.
*   **Purpose:** Groups related translations for bulk loading and caching. When a frontend app loads "auth" translations, it gets all keys in that domain.
*   **Enforcement:** You cannot create a translation key unless the `domain` exists in this table and is `is_active=1`.

## 3. Domain-Scope Mapping (`i18n_domain_scopes`)

This is the core policy engine.

*   **Definition:** A many-to-many relationship linking a `domain` to one or more `scopes`.
*   **Example:**
    *   `auth` domain might be linked to both `web` and `admin` scopes (shared authentication logic).
    *   `dashboard` domain might be linked ONLY to `admin` scope.
*   **Enforcement:**
    *   If you try to create a key `web.dashboard.title`, the system checks:
        1.  Does scope `web` exist? (Yes)
        2.  Does domain `dashboard` exist? (Yes)
        3.  Is `dashboard` allowed for `web` scope? (**NO**)
    *   **Result:** The operation fails with `DomainScopeViolationException`.

## 4. The Policy Service

The `I18nGovernancePolicyService` is the gatekeeper. It is injected into all write services (`TranslationWriteService`, etc.).

### Modes
The service supports two modes, controlled via `I18nPolicyModeEnum`:

1.  **STRICT (Default):**
    *   Throws exceptions if Scope is missing/inactive.
    *   Throws exceptions if Domain is missing/inactive.
    *   Throws exceptions if Domain is not mapped to Scope.

2.  **PERMISSIVE:**
    *   Allows operations if the Scope or Domain record is *physically missing* from the database (bypassing the check).
    *   Still enforces `is_active` checks if the records exist.
    *   Still enforces mapping if both records exist.
    *   *Note: This mode is generally discouraged in production but useful during initial migration or development.*

### Usage in Code

```php
// Creating the service (usually done via Dependency Injection)
$governance = new I18nGovernancePolicyService(
    $scopeRepo,
    $domainRepo,
    $domainScopeRepo,
    I18nPolicyModeEnum::STRICT
);

// Manual Assertion
try {
    $governance->assertScopeAndDomainAllowed('admin', 'billing');
} catch (DomainScopeViolationException $e) {
    // Handle violation: "Domain 'billing' is not allowed for scope 'admin'"
}
```
