# Architectural Audit: Modules/I18n

## Executive Summary

**Verdict: READY (Conditioned on Documentation Updates)**

The `Modules/I18n` module is architecturally sound, internally consistent, and well-isolated. It successfully separates persistence (Repositories) from business logic (Services) and strictly types data transfer (DTOs). The core "Read" path is fail-soft and highly decoupled, making it suitable for high-performance usage in consumer apps. However, the documentation (README.md) is significantly outdated and misleading, failing to mention the entire Governance layer (Scopes, Domains) and providing incorrect usage examples. Once the documentation is aligned with the implementation, the module is ready for extraction as a standalone library.

## Architecture Assessment

### Strengths
1.  **Strict Separation of Concerns**: The module enforces a clear boundary between `Infrastructure` (Persistence) and `Service` (Business Logic). Repositories are largely dumb adapters, while services handle validation and orchestration.
2.  **Robust Governance Model**: The implementation includes a comprehensive governance layer (`I18nGovernancePolicyService`, `Scope`, `Domain`) that allows for strict control over translation keys, preventing key pollution.
3.  **Fail-Soft Read Path**: The `TranslationReadService` is designed to be safe for runtime consumption, returning `null` instead of throwing exceptions, which is critical for frontend resilience.
4.  **Type Safety**: The pervasive use of DTOs (`TranslationKeyDTO`, `LanguageDTO`) ensures that data structures are predictable and typed across boundaries.
5.  **Schema Design**: The database schema is normalized and supports the governance model effectively, with clear separation between System Identity (`languages`) and Content (`i18n_translations`).

### Weaknesses
1.  **Documentation Lag**: The `README.md` describes a much simpler system than what is implemented, omitting 3 core tables and the entire governance concept.
2.  **Minor Inconsistency in Repositories**: While most repository methods return `null` or empty collections on failure, `MysqlLanguageSettingsRepository::getNextSortOrder` throws a `RuntimeException`, violating the "No exceptions" rule stated in the README.
3.  **Read Service Signature**: The README example implies a simplified `getValue($lang, $key)` signature, whereas the actual service requires `getValue($lang, $scope, $domain, $key)`, which increases the integration burden for consumers who might expect a flatter key structure.

## Boundary Violations

**File**: `Modules/I18n/Infrastructure/Mysql/MysqlLanguageSettingsRepository.php`
**Line**: ~235 (inside `getNextSortOrder`)
**Violation**:
```php
if ($stmt === false) {
    throw new \RuntimeException('Failed to fetch next sort order.');
}
```
**Reason**: The README explicitly states: "Repositories never throw business exceptions... Any PDO failure -> return null or empty collection". While `RuntimeException` is technically an infrastructure error, strict adherence to the pattern would suggest returning a default value (e.g., `1`) or `null` rather than crashing.

## Code vs README Mismatches

| Feature | README Claim | Actual Code Implementation |
| :--- | :--- | :--- |
| **Schema** | Lists 4 tables: `languages`, `language_settings`, `i18n_keys`, `i18n_translations`. | Contains 7 tables: Adds `i18n_scopes`, `i18n_domains`, `i18n_domain_scopes`. |
| **Read API** | Example: `$i18n->getValue('ar', 'login.button.submit')` (2 args). | Actual: `$service->getValue('ar', 'ct', 'auth', 'login.button.submit')` (4 args). |
| **Governance** | Not mentioned. | Fully implemented `I18nGovernancePolicyService` enforcing Scope/Domain rules on write. |
| **Key Structure** | Implies flat keys. | Enforces structured keys (`scope`, `domain`, `key_part`). |

## Required Changes

The following changes are required to align the module with its contract and ensure "Library Readiness":

1.  **Update README.md**:
    *   Add the missing Governance tables (`i18n_scopes`, `i18n_domains`, `i18n_domain_scopes`) to the Schema section.
    *   Document the "Structured Key" requirement (Scope + Domain + KeyPart).
    *   Update the `TranslationReadService` usage example to reflect the actual 4-argument signature.
    *   Add a section describing the Governance/Policy layer.

2.  **Fix Repository Exception**:
    *   Modify `MysqlLanguageSettingsRepository::getNextSortOrder` to catch the failure and return a default (e.g., `1`) instead of throwing `RuntimeException`, strictly adhering to the "No Exceptions" rule.

## Final Verdict

**IS THIS MODULE CLOSED AND STABLE?**

**YES** (Provisionally).

The code itself is stable, mature, and follows a consistent architectural pattern. The governance mechanisms are a valuable addition that ensure long-term maintainability. The primary barrier to "release" is the inaccurate documentation, which would mislead any integrator. Once the README is updated to accurately reflect the 7-table schema and the structured key requirement, this module is **READY** for extraction.
