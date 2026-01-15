# Context Closure Canonical Audit

> **Status:** READY (with minor cleanup items)
> **Auditor:** Jules
> **Date:** 2026-01-XX

## 1. Executive Summary

The repository is **fully aligned** with the canonical Context Injection model.
- No forbidden legacy context access patterns were found in active code.
- Middleware order is correct and verified (LIFO).
- `request_id` fail-closed enforcement is present in DTOs and Middleware.
- Documentation has minor gaps regarding internal plumbing but is largely accurate.

Two minor breaches were identified:
1.  A test file named after a deleted class (`HttpContextProviderRegressionTest`) exists (Logic is valid/modern, name is legacy).
2.  An integration test (`PdoActivityLogListReaderTest`) uses partial dates in `ListQueryDTO`, which is strictly forbidden in the Canonical API, showing a slight looseness in the DTO constructor vs API contract.

## 2. Breach List

| Severity | File | Issue | Why it violates canonical | Suggested Fix |
| :--- | :--- | :--- | :--- | :--- |
| **LOW** | `tests/Integration/Context/HttpContextProviderRegressionTest.php` | **Ghost Test File**. The test verifies `SessionGuard` -> `AdminContext` flow (correct), but the filename refers to `HttpContextProvider` which is deleted. | Violates "Clean Code" / Confusion. Implies legacy component existence. | Rename to `tests/Integration/Context/AdminContextMiddlewareTest.php` or similar. |
| **LOW** | `tests/Integration/ActivityLog/PdoActivityLogListReaderTest.php` | **Internal Consistency**. Test constructs `ListQueryDTO` with `dateTo: null`. | Canonical List Query requires `from` AND `to` if date is present. DTO allows partials, API Validation blocks it. | Ensure `ListQueryDTO` constructor enforces strict pair OR verify Repository explicitly handles open-ended ranges and document the divergence (API strict, Repo loose). |

## 3. Middleware Order Proof

**File:** `routes/web.php`
**Mechanism:** Slim Framework LIFO Stack (`$app->add()` executes last-added first).

### A) Request Context Chain
**Observed Order (Code):**
```php
$app->add(\App\Http\Middleware\RequestContextMiddleware::class); // Runs 2nd
$app->add(\App\Http\Middleware\RequestIdMiddleware::class);      // Runs 1st
```
**Verification:**
- `RequestContextMiddleware` throws `RuntimeException` if `request_id` attribute is missing.
- `RequestIdMiddleware` guarantees `request_id` attribute is set (using UUID v4 fallback).
**Result:** PASSED.

### B) Admin Context Chain
**Observed Order (Code):**
```php
->add(\App\Http\Middleware\AdminContextMiddleware::class) // Runs 2nd (Consumer)
->add(SessionGuardMiddleware::class)                      // Runs 1st (Producer)
```
**Verification:**
- `SessionGuardMiddleware` validates token -> sets `admin_id` attribute.
- `AdminContextMiddleware` reads `admin_id` attribute -> sets `AdminContext` attribute.
**Result:** PASSED.

## 4. Inventory Tables

### Forbidden Patterns (Scope A)
| Pattern | Matches | Verdict |
| :--- | :--- | :--- |
| `getAttribute('admin_id')` (outside AdminContextMiddleware) | 0 | PASSED |
| `withAttribute('admin_id')` (outside SessionGuardMiddleware) | 0 | PASSED |
| `HttpContextProvider` (Usage) | 0 (only in legacy test filename) | PASSED |
| `ContextProviderMiddleware` | 0 | PASSED |
| `WebClientInfoProvider` | 0 | PASSED |
| `$_SERVER` (Logic usage) | 0 (only in RequestContextMiddleware) | PASSED |

### Audit/Security Contracts (Scope C)
| Contract | Implementation | Verified |
| :--- | :--- | :--- |
| `SecurityEventDTO` | Injects `request_id` in constructor. | YES |
| `AuditEventDTO` | Requires `request_id` in constructor. | YES |

## 5. Test Suite Findings

*   **Invalid Tests**: None found (functionally).
*   **Renaming Candidate**: `tests/Integration/Context/HttpContextProviderRegressionTest.php` -> Content is valid, Name is stale.
*   **Contract Violation**: `tests/Integration/ActivityLog/PdoActivityLogListReaderTest.php` uses `dateFrom` without `dateTo`. While the Reader accepts it, the Canonical API forbids it. This test exercises a path that is unreachable via the Canonical API.

## 6. PROJECT_CANONICAL_CONTEXT.md Gap Analysis

The following edits are proposed to `docs/PROJECT_CANONICAL_CONTEXT.md`.

### A. Missing: Admin ID Attribute Internal Status
**Location:** Insert in `## E) Routing & Middleware Contract` -> `2. Middleware Pipeline (Observed)` or a new subsection.
**Proposal:**
```markdown
#### Internal Context Plumbing (LOCKED)
*   **`admin_id` Attribute**: The `admin_id` request attribute is strictly an **internal implementation detail** for signaling between `SessionGuardMiddleware` (Producer) and `AdminContextMiddleware` (Consumer).
*   **Forbidden Usage**: Controllers and Services MUST NOT access `getAttribute('admin_id')`. They MUST consume `AdminContext` request attribute.
```

### B. Missing: Request ID Fail-Closed
**Location:** Insert in `## D) Logging Policy (HARD)` -> `D.1 Audit Logs` or `D.2 Security Events`.
**Proposal:**
```markdown
**Context Injection Rule:**
*   All Audit and Security DTOs MUST enforce `request_id` presence via constructor injection.
*   The system MUST fail-closed if `request_id` is missing/empty during event creation.
```

### C. Clarification: Middleware LIFO
**Location:** `## E) Routing & Middleware Contract` -> `2. Middleware Pipeline (Observed)`
**Proposal:**
```markdown
*   **Execution Order**: The pipeline follows Slim's LIFO (Last-In-First-Out) execution model.
    *   `RequestIdMiddleware` runs BEFORE `RequestContextMiddleware`.
    *   `SessionGuardMiddleware` runs BEFORE `AdminContextMiddleware`.
```

## 7. Exit Criteria Checklist
- [x] Repo-wide scan for forbidden patterns (0 violations).
- [x] Middleware order verified (LIFO confirmed).
- [x] Audit/Security `request_id` enforcement verified.
- [x] Test suite audited (1 ghost file identified).
- [x] Doc gap analysis complete.
