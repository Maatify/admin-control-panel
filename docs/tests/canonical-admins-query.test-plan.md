# Canonical Admins Query Test Plan (AS-IS)

## Scope
This test plan covers the **contract compliance** of the `POST /api/admins/query` endpoint behavior, specifically targeting the AS-IS state of the codebase.

## Components Tested
1.  **SharedListQuerySchema**: Validates request structure enforcement.
2.  **ListQueryDTO**: Validates data normalization and default values.
3.  **ListFilterResolver**: Validates capability-based filter resolution (whitelisting).
4.  **PdoAdminQueryReader**: Validates SQL generation logic for search and filters.

## Test Coverage (AS-IS)

### 1. Schema Enforcement (`SharedListQuerySchema`)
- **Pass**: Accepts valid canonical shape.
- **Fail**: Rejects forbidden keys (`filters`, `limit`, `items`, `meta`, `from_date`, `to_date`).
- **Fail**: Rejects empty `search` block.
- **Fail**: Rejects partial `date` range (must have both `from` and `to`).

### 2. DTO Normalization (`ListQueryDTO`)
- **Pass**: Defaults `page` to 1.
- **Pass**: Defaults `per_page` to 20.
- **Pass**: Normalizes empty or missing optional blocks to `null`/empty array.
- **Pass**: Trims strings in global search.

### 3. Resolver Logic (`ListFilterResolver`)
- **Pass**: Resolves allowed aliases (`id`, `email`).
- **Pass**: Silently drops unknown column aliases (e.g. `email_encrypted`).
- **Pass**: Propagates global search if capability is enabled.

### 4. Reader Logic (`PdoAdminQueryReader`)
- **Global Search**:
    - **Pass**: Matches ID (exact match) when input is numeric.
    - **Pass**: Matches Email (blind index match) when input is email format.
    - **Pass**: Ignores invalid global search terms (neither numeric nor email).
- **Column Search**:
    - **Pass**: Applies `id` filter (exact match).
    - **Pass**: Applies `email` filter (blind index match).
- **Date Filter**:
    - **Pass**: Applies `created_at` range filter (`>=` from, `<=` to).

## Exclusions
- **Future Features**: Roles, Status, Verification status filters are NOT tested as they are not currently implemented in `PdoAdminQueryReader`.
- **Legacy Endpoints**: `GET` based endpoints are strictly excluded.
- **Full Integration**: Tests use mocks for PDO to verify SQL intent without requiring a live database connection.

## Verification Result
All 12 contract tests passed, confirming the implementation adheres to the defined AS-IS contract.
