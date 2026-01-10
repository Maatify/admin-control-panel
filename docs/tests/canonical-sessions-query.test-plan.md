# Canonical Sessions Query Test Plan

## Overview
This test plan covers the **Contract Tests** for the `POST /api/sessions/query` endpoint, strictly enforcing the **Canonical LIST / QUERY** pattern as defined in `docs/API_PHASE1.md`.

## Purpose & Scope
This phase focuses exclusively on the **Sessions** resource because it is designated as the **Reference Implementation** for the Canonical List/Query architecture (`docs/API_PHASE1.md`, Section F.1). Verifying strict compliance here establishes the baseline for all future list endpoints.

## Test Scope
*   **Endpoint:** `POST /api/sessions/query`
*   **Type:** Contract / Unit / Integration (Boundary)
*   **Allowed Components:**
    *   `SharedListQuerySchema` (Validation)
    *   `ListQueryDTO` (Data Transfer)
    *   `ListFilterResolver` (Filter Logic)
    *   `PdoSessionListReader` (SQL Generation & Boundary)

## Test Coverage

### 1. SharedListQuerySchema
Tests the validation rules defined in the contract.
*   **Forbidden Keys:** Verifies that keys like `filters`, `limit`, `items`, `meta`, `from_date`, `to_date` are REJECTED.
*   **Structure:** Verifies that empty `search` blocks are rejected.
*   **Partial Date:** Verifies `date` requires both `from` and `to`.

### 2. ListQueryDTO
Tests the normalization of input data.
*   **Defaults:** `page`=1, `per_page`=20.
*   **Clamping:** `page` < 1 -> 1.
*   **Trimming:** `search.global` is trimmed.
*   **Date Parsing:** Strings converted to `DateTimeImmutable`.
*   **Structure:** Empty optional blocks are handled correctly.

### 3. ListFilterResolver (Sessions Context)
Tests the resolution of filters against Session capabilities.
*   **Aliases:**
    *   Accepts: `session_id`, `admin_id`, `status`.
    *   Rejects: `real_column_name` (e.g. `is_revoked`), unknown aliases.
*   **Global Search:** Verifies global search is passed through.
*   **Date Filter:** Verifies date filter is passed through.

### 4. PdoSessionListReader
Tests the SQL generation and logic (Boundary).
*   **Global Search:**
    *   Matches `session_id` (LIKE).
    *   Matches `admin_id` (Exact) **ONLY** if numeric.
    *   Matches `status` (Derived CASE WHEN).
*   **Column Search:**
    *   `session_id` -> LIKE.
    *   `admin_id` -> Exact match.
    *   `status` -> CASE logic (`active`, `revoked`, `expired`).
*   **Date Filter:**
    *   Applied to `created_at`.
*   **Pagination:**
    *   `LIMIT` / `OFFSET` applied correctly.

## Out of Scope
*   `GET` endpoints (Legacy).
*   `Admins` or other resources (Not verified in this phase).
*   Legacy code/controllers.
*   Full HTTP/Controller stack (Mocking at component level).

## Failure Strategy
*   Tests enforcing strict contract (e.g., "Reject forbidden keys") **MUST FAIL** if the current implementation (`AbstractSchema`) is permissive.
*   **Result:** Tests are failing as expected due to known contract violations in `SharedListQuerySchema`.
*   No code fixes will be applied. Failures are reported as findings.
