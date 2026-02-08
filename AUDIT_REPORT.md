# I18N_SCOPES_UI.md — Contract Audit Report

## Summary
- Overall compliance: PARTIAL
- Number of violations found: 2

## Violations

### ❌ Violation #1
- Location: Section 4) Create Scope / Request Payload
- Statement: `is_active` | bool | No | Defaults to `true` (1).
- Reason: The controller uses `is_numeric($body['is_active'])` to validate this field. In PHP, `is_numeric(true)` and `is_numeric(false)` return `false`. Consequently, sending a JSON boolean `false` results in the check failing and the code falling back to the default `1` (true), making it impossible to create an inactive scope using a boolean value. The effective required type is `int` (0/1).
- Source of truth missing: `I18nScopeCreateController.php` (logic ignores boolean types).

### ❌ Violation #2
- Location: Section 8) Toggle Active / Request Payload
- Statement: `is_active` | bool | **Yes** | New state.
- Reason: The controller uses `is_numeric($body['is_active'])` to validate this field. Sending a JSON boolean `false` causes the check to fail and the value to default to `1` (true). Thus, it is impossible to set a scope to inactive using a boolean `false`. The effective required type is `int` (0/1).
- Source of truth missing: `I18nScopeSetActiveController.php` (logic ignores boolean types).

## Confirmed Valid Sections
- Section 0) Why this document exists
- Section 1) Page Architecture
- Section 2) Capabilities (Authorization Contract) - matches `ScopesListUiController`
- Section 3) List Scopes (table) - matches `SharedListQuerySchema`, `I18nScopesListCapabilities`, and `PdoI18nScopesQueryReader`
- Section 5) Change Scope Code - matches `I18nScopeChangeCodeSchema`
- Section 6) Update Sort Order - matches `I18nScopeUpdateSortSchema`
- Section 7) Update Metadata - matches `I18nScopeUpdateMetadataSchema`
- Section 9) Implementation Checklist

## Final Verdict
The documentation is highly accurate regarding structural contracts, capabilities, and strict sorting rules. However, it fails to accurately reflect the implementation reality for `is_active` fields, where the backend code requires integer `0`/`1` despite the schema allowing booleans. The documentation's claim that `bool` is supported is functionally incorrect because the controller logic forces a default of `true` for any boolean input.
