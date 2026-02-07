# 03. Governance Model

## Overview

The `maatify/i18n` library employs a strict governance model to prevent key sprawl and ensure referential integrity.

This model is enforced by the **`I18nGovernancePolicyService`** during write operations. Runtime reads (fail-soft) do not throw governance exceptions but will return empty/null results if governance rules are violated.

## 1. Scopes (`i18n_scopes`)

Scopes define the top-level application boundaries.

*   **Enforcement:** A translation key cannot be created unless its `scope` exists in this table and has `is_active=1`.
*   **Purpose:** Ensures strict separation of concerns (e.g., `admin` keys cannot be loaded into `client` context).

## 2. Domains (`i18n_domains`)

Domains represent functional areas within a scope.

*   **Enforcement:** A translation key cannot be created unless its `domain` exists in this table and has `is_active=1`.
*   **Purpose:** Groups related translations for bulk loading and caching.

## 3. Domain-Scope Mapping (`i18n_domain_scopes`)

This table defines the permitted relationships between Scopes and Domains.

*   **Definition:** A many-to-many relationship linking a `domain` to one or more `scopes`.
*   **Enforcement:**
    *   If you attempt to create a key `web.dashboard.title`:
        1.  Does scope `web` exist? (Yes)
        2.  Does domain `dashboard` exist? (Yes)
        3.  Is `dashboard` explicitly mapped to `web` in `i18n_domain_scopes`? (**NO**)
    *   **Result:** The operation fails immediately with `DomainScopeViolationException`.

## 4. The Policy Service

The `I18nGovernancePolicyService` acts as the gatekeeper. It is injected into all write services (`TranslationWriteService`, etc.).

### Modes
The service operates in two modes, controlled via `I18nPolicyModeEnum`:

1.  **STRICT (Default):**
    *   Throws exceptions if Scope is missing/inactive.
    *   Throws exceptions if Domain is missing/inactive.
    *   Throws exceptions if Domain is not mapped to Scope.

2.  **PERMISSIVE:**
    *   Allows operations if the Scope or Domain record is physically missing from the database (bypassing the check).
    *   Still enforces `is_active` checks if the records exist.
    *   Still enforces mapping if both records exist.
    *   *Note: This mode is exclusively for initial migration or development and must not be used in production.*

### Usage in Code

```php
// Creating the service (via Dependency Injection)
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
