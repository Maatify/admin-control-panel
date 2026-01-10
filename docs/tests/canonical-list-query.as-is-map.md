# Canonical LIST / QUERY — AS-IS Mapping

> ⚠️ AS-IS DOCUMENT — NOT A TARGET
>
> This document describes the current observed state of LIST / QUERY
> implementations in the codebase.
>
> It is NOT a canonical specification.
> It does NOT authorize legacy patterns.
> It exists solely to:
> - define test scope
> - prevent architectural drift
> - document deviations explicitly

This document maps the current state of **LIST / QUERY** components in the codebase, distinguishing between the **Canonical Pattern** (standardized POST query) and the **Legacy/Ad-hoc Pattern** (GET or manual parsing).

## 1. Canonical Pattern (Standard)

The Canonical Pattern enforces a strict `POST` contract with a validated JSON payload, a shared DTO, and a capability-based filter resolver.

### 1.1 Shared Infrastructure

| Component            | Class                                                  | Description                                                                         |
|:---------------------|:-------------------------------------------------------|:------------------------------------------------------------------------------------|
| **Input Schema**     | `App\Modules\Validation\Schemas\SharedListQuerySchema` | Validates `page`, `per_page`, `search` (global/columns), `date` (from/to).          |
| **Generic DTO**      | `App\Domain\List\ListQueryDTO`                         | Immutable object representing the normalized query. Instantiated via `fromArray()`. |
| **Capabilities**     | `App\Domain\List\ListCapabilities`                     | Defines supported features (global search, specific columns, date filtering).       |
| **Resolver**         | `App\Infrastructure\Query\ListFilterResolver`          | Maps `ListQueryDTO` + `ListCapabilities` → `ResolvedListFilters`.                   |
| **Resolved Filters** | `App\Infrastructure\Query\ResolvedListFilters`         | Safe, resolved criteria passed to Readers.                                          |

### 1.2 Implementations

#### A. Admin Query (`POST /api/admins/query`)

| Layer            | Component                                             | Notes                                                |
|:-----------------|:------------------------------------------------------|:-----------------------------------------------------|
| **Controller**   | `App\Http\Controllers\Api\AdminQueryController`       | Uses `ValidationGuard` with `SharedListQuerySchema`. |
| **Capabilities** | `App\Domain\List\AdminListCapabilities`               | Static definition of admin-specific capabilities.    |
| **Reader**       | `App\Infrastructure\Reader\Admin\PdoAdminQueryReader` | Implements blind index lookup for email search.      |
| **Response**     | `App\Domain\DTO\AdminList\AdminListResponseDTO`       | Contains `AdminListItemDTO` and `PaginationDTO`.     |

#### B. Session Query (`POST /api/sessions/query`)

| Layer            | Component                                                | Notes                                                                                                             |
|:-----------------|:---------------------------------------------------------|:------------------------------------------------------------------------------------------------------------------|
| **Controller**   | `App\Http\Controllers\Api\SessionQueryController`        | Uses `ValidationGuard`. Enforces RBAC scopes manually.                                                            |
| **Capabilities** | `App\Domain\List\ListCapabilities`                       | Instantiated inline with `session_id`, `status`, `admin_id` filters.                                              |
| **Reader**       | `App\Infrastructure\Reader\Session\PdoSessionListReader` | Implements session status logic (active/revoked/expired) in SQL. Supports `admin_id` in Global and Column search. |
| **Response**     | `App\Domain\DTO\Session\SessionListResponseDTO`          | Contains `SessionListItemDTO` and `PaginationDTO`.                                                                |

> **Note on Session Query Features:**
> The Session Query endpoint explicitly supports `admin_id` search in two modes:
> 1. **Global Search:** Matches `session_id` (LIKE) OR `admin_id` (Exact, if numeric) OR `status` (derived).
> 2. **Column Search:** Matches `admin_id` (Exact) via `search.columns.admin_id`.
>
> This is an **INTENTIONAL FEATURE ADDITION** and works alongside strict RBAC scope enforcement (AND logic).
>
> **Global Search Status Support:**
> Global search matches `status` by reusing the derived CASE WHEN logic:
> `(CASE ... END) LIKE :global`.

---

## 2. Legacy / Ad-hoc Pattern

These controllers do **not** follow the Canonical Pattern. They typically use `GET` requests, manual input parsing, or custom schemas.

| Feature                  | Controller                           | Input Method         | Validation                                           | DTO                                         | Reader                                    |
|:-------------------------|:-------------------------------------|:---------------------|:-----------------------------------------------------|:--------------------------------------------|:------------------------------------------|
| **Notification History** | `AdminNotificationHistoryController` | `GET` / Query Params | `ValidationGuard` + `AdminNotificationHistorySchema` | `AdminNotificationHistoryQueryDTO` (Custom) | `AdminNotificationHistoryReaderInterface` |
| **Security Events**      | `AdminSecurityEventController`       | `GET` / Query Params | Manual `max(1, ...)` inline                          | `GetMySecurityEventsQueryDTO` (Custom)      | `AdminSecurityEventReaderInterface`       |
| **Self Audit**           | `AdminSelfAuditController`           | `GET` / Query Params | Manual `max(1, ...)` inline                          | `GetMyActionsQueryDTO` (Custom)             | `AdminSelfAuditReaderInterface`           |
| **Targeted Audit**       | `AdminTargetedAuditController`       | `GET` / Query Params | Manual `max(1, ...)` inline                          | `GetActionsTargetingMeQueryDTO` (Custom)    | `AdminTargetedAuditReaderInterface`       |

### Key Differences
1.  **Input:** Uses `GET` query parameters instead of a structured JSON `POST` body.
2.  **Validation:** Audit controllers lack `ValidationGuard` and rely on manual casting/clamping.
3.  **DTOs:** Each has a unique, non-shared DTO structure.
4.  **Consistency:** Date parsing and pagination logic is duplicated across controllers.

---

## 3. Discrepancies & Observations

| File/Component                               | Status           | Observation                                                                                                                                                     |
|:---------------------------------------------|:-----------------|:----------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `App\Domain\DTO\AdminList\AdminListQueryDTO` | **Unused**       | Defined but not used by `AdminQueryController` (which uses `App\Domain\List\ListQueryDTO`). Appears to be an artifact.                                          |
| **Audit Controllers**                        | **Inconsistent** | `AdminSecurityEventController` and Audit controllers do not use the Validation Module, unlike `AdminNotificationHistoryController`.                             |
| **Date Parsing**                             | **Duplicated**   | Date parsing logic (`DateTimeImmutable::createFromFormat`) is repeated in every Ad-hoc controller, whereas Canonical controllers use `ListQueryDTO::fromArray`. |
