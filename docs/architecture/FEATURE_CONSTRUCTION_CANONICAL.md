# Feature Construction Canonical Guide
## Maatify Admin Control Panel

**Status:** LOCKED / CANONICAL  
**Scope:** Backend + API + UI Coordination  
**Audience:** All contributors (Backend / Frontend / AI Executors)

This document defines the **mandatory, unified pattern** for building any new
feature, route, page, or module in the Admin Control Panel.

No deviations are allowed.

---

## 1. Purpose

This document exists to:

- Eliminate repeated explanations of “how we build things”
- Prevent architectural drift and personal styles
- Provide a single, stable execution model for all features
- Act as a permanent reference for onboarding and reviews

If a feature does not follow this document, it is considered **invalid**.

---

## 2. First Decision: Feature Type

Before writing any code, every feature **must** be classified as one of the following:

| Type               | Description                                                         |
|--------------------|---------------------------------------------------------------------|
| **Query**          | Read-only data retrieval (tables, lists, pagination, filters)       |
| **Action**         | State mutation (assign, unassign, toggle, update)                   |
| **UI Page**        | HTML/Twig page                                                      |
| **Composite Page** | One page containing multiple independent datasets (Tabs / Sections) |

A **Composite Page** is not a feature by itself.
It is a container that hosts multiple independent features,
each of which must individually follow this document.


### Hard Rules
- A feature **must belong to exactly one type**
- Query logic must never be mixed with Action logic
- UI pages must never contain business logic or data access

---

## 3. API Query Pattern (Mandatory)

### 3.1 When to Use a Query
Any data presentation that includes:
- Tables
- Pagination
- Filters
- Search
- Sorting

### 3.2 Route Shape (Strict)

```http
POST /api/{resource}/{context}/query
```

**Examples:**

```http
POST /api/roles/query
POST /api/roles/{id}/permissions/query
POST /api/roles/{id}/admins/query
```

Route parameters may use regex constraints for routing purposes,
but must never be relied upon for validation or security.
All validation is enforced at schema level.


### Forbidden

* `GET` for lists
* Query parameters (`?page=`, `?filter=`)
* Mixed action/query endpoints

---

## 4. API Query – Internal Construction Order

Every Query **must follow this order exactly**.

### 4.1 Validation Schema

**Location:**

```
Modules/Validation/Schemas/
```

* `SharedListQuerySchema` is used by default
* Custom schemas are allowed only for additional required fields

Validation never happens in controllers or repositories.

---

### 4.2 ListQueryDTO (Single Source)

**Location:**

```
Domain/List/ListQueryDTO
```

Rules:

* Always used
* No alternative DTOs
* No raw arrays

---

### 4.3 List Capabilities (Not RBAC)

**Location:**

```
Domain/List/*Capabilities.php
```

Capabilities describe **what the query supports**, not permissions.

Example:

```php
new ListCapabilities(
    supportsGlobalSearch: true,
    searchableColumns: [...],
    supportsColumnFilters: true,
    filterableColumns: [...],
    supportsDateFilter: false
);
```

---

### 4.4 Filter Resolution (Mandatory)

```php
$filters = $this->filterResolver->resolve($query, $capabilities);
```

Forbidden:

* Manual filter parsing
* Accessing request body directly
* Custom query logic outside resolver output

---

### 4.5 Reader (Query Execution)

Exception:
If a repository manages both read and write operations
for the same aggregate root, query methods are allowed
only if they strictly follow the Reader rules
(no business logic, no side effects).

**Rule: Queries never use regular repositories**

**Location:**

```
Infrastructure/Reader/
```

Responsibilities:

* SQL construction
* Filter application
* Pagination
* Mapping to DTOs

No business logic.

---

### 4.6 Response DTO (Mandatory)

Every Query returns:

```php
new XQueryResponseDTO(
    data: [...],
    pagination: new PaginationDTO(...)
)
```

Forbidden:

* Returning arrays
* Encoding JSON inside repositories
* Returning partial responses

---

## 5. API Action Pattern (Assign / Toggle / Update)

### 5.1 Route Shape

```http
POST /api/{resource}/{context}/{action}
```

**Examples:**

```http
POST /api/roles/{id}/permissions/assign
POST /api/roles/{id}/admins/unassign
```

---

### 5.2 Validation Schema (Mandatory)

Each Action has its own schema, even if it validates a single field.

---

### 5.3 Repository (Action Execution)

**Location:**

```
Domain/Contracts/
Infrastructure/Repository/
```

Rules:

* One method per action
* Repositories may contain multiple actions for the same entity
* Actions must be idempotent where applicable

---

### 5.4 Action Responses

* Success: `204 No Content`
* Failure: Exception-based (validation, authorization, step-up)

Forbidden:

* Returning payloads
* `{ success: true }` patterns

---

## 6. UI Page Pattern

### 6.1 UI Controller Responsibilities

UI controllers are responsible **only** for:

* Context identifiers (IDs)
* Capability exposure
* Page composition

Forbidden:

* Database queries
* Business logic
* Feature decisions

---

### 6.2 Capability Contract (RBAC → Frontend)

Controllers expose explicit capability flags:

```php
$capabilities = [
    'can_view_x' => bool,
    'can_assign_x' => bool,
];
```

Frontend rules:

* Never infer permissions
* Never assume visibility
* Use capabilities strictly for UI decisions

---

## 7. Composite Pages (Tabs / Multiple Datasets)

### 7.0 First-Level Tabbed Pages

A tabbed page may be the first page of a feature
(no parent list required).

Example:
- Role Details Page
- Admin Profile Page

Tabs do not imply hierarchy.
They imply multiple datasets bound to the same context identifier.

---

### 7.1 When to Use

A composite page is used when:

* One entity page
* Multiple independent datasets
* Each dataset can grow independently

---

### 7.2 Core Rule

> **Each Tab is a complete, standalone feature**

This means:

* Independent API
* Independent permissions
* Independent JS module
* Independent state

---

### 7.3 File Structure

```
role-details.twig
role-details-tabs.js        // tab switcher only
role-details-permissions.js
role-details-admins.js
```

---

### 7.4 Tabs Manager

Responsibilities:

* UI switching only
* Event dispatching

Example:

```js
document.dispatchEvent(new CustomEvent('roleTabLoaded', { detail: 'permissions' }));
```

No API calls. No data logic.

---

### 7.5 Tab Modules

Each tab:

* Listens to its event
* Lazy-loads data
* Owns its table
* Owns its actions and toggles

---

## 8. Tables & Pagination

* Shared table JS is reused
* Containers are swapped per tab
* Core table logic is never duplicated

---

## 9. Route Regex Policy

* Regex is allowed in routes
* Regex is never relied upon for validation
* All validation happens in schemas

Regex = routing optimization
Schema = security boundary

---

## 10. DTO Rules (Non-Negotiable)

* DTOs are immutable
* Fully typed
* Implement `JsonSerializable`
* Response DTOs are distinct from Item DTOs
* Pagination is always an object, never an array

---

## 11. Feature Construction Checklist

Every new feature must follow this sequence:

1. Classify feature type
2. Define route
3. Create validation schema
4. Use `ListQueryDTO` (for queries)
5. Define capabilities
6. Implement Reader or Repository
7. Create DTOs
8. Expose capabilities to UI
9. Implement isolated JS module
10. Verify no rule above is violated

---

## 12. Feature Skeleton Template (Mandatory)

This section defines the **exact file structure and responsibilities**
for any new feature in the system.

No file may be added outside these locations.

---

## 12.1 High-Level Feature Map

Every feature is composed of **three layers only**:

```

UI Layer        → Controllers (Twig + Capabilities)
API Layer       → Controllers (Query / Action)
Domain Layer    → DTOs + Contracts
Infrastructure  → Readers / Repositories

```

There is **no fourth layer**.

---

## 12.2 Query Feature – File Skeleton

### Example Feature
```

Role → Permissions Tab
POST /api/roles/{id}/permissions/query

```

### Required Files

```

Modules/
└── AdminKernel/
    ├── Http/
    │   └── Controllers/
    │       ├── Api/
    │       │   └── Roles/
    │       │       └── RolePermissionsQueryController.php
    │       └── Ui/
    │           └── UiRoleDetailsController.php
    │
    ├── Domain/
    │   ├── DTO/
    │   │   ├── Roles/
    │   │   │   ├── RolePermissionListItemDTO.php
    │   │   │   └── RolePermissionsQueryResponseDTO.php
    │   │   └── Common/
    │   │       └── PaginationDTO.php
    │   │
    │   ├── Contracts/
    │   │   └── Roles/
    │   │       └── RolePermissionsRepositoryInterface.php
    │   │
    │   └── List/
    │       └── RolePermissionsCapabilities.php
    │
    ├── Infrastructure/
    │   ├── Reader/        (if read-only)
    │   └── Repository/   (if mixed or action-based)
    │       └── Roles/
    │           └── PdoRolePermissionsRepository.php

```

---

## 12.3 PaginationDTO (Canonical)

### Location
```

Domain/DTO/Common/PaginationDTO.php

```

### Purpose
Pagination is **always a value object**, never an array.

It represents **query metadata**, not UI state.

### Canonical Structure

```php
readonly class PaginationDTO implements JsonSerializable
{
    public function __construct(
        public int $page,
        public int $perPage,
        public int $total,
        public int $filtered
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'page'      => $this->page,
            'per_page' => $this->perPage,
            'total'    => $this->total,
            'filtered' => $this->filtered,
        ];
    }
}
````

### Rules

* `total` = total rows without filters
* `filtered` = rows after filters
* Frontend never recalculates these values

---

## 12.4 List Item DTO Pattern

### Location

```
Domain/DTO/{Feature}/{Feature}ListItemDTO.php
```

### Rules

* Represents **one row only**
* No pagination
* No metadata
* No formatting logic

Example:

```php
readonly class RolePermissionListItemDTO implements JsonSerializable
{
    public function __construct(
        public int $id,
        public string $name,
        public bool $assigned
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'id'       => $this->id,
            'name'     => $this->name,
            'assigned' => $this->assigned,
        ];
    }
}
```

---

## 12.5 Query Response DTO Pattern

### Location

```
Domain/DTO/{Feature}/{Feature}QueryResponseDTO.php
```

### Structure (Mandatory)

```php
readonly class XQueryResponseDTO implements JsonSerializable
{
    /**
     * @param XListItemDTO[] $data
     */
    public function __construct(
        public array $data,
        public PaginationDTO $pagination
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'data'       => $this->data,
            'pagination' => $this->pagination,
        ];
    }
}
```

### Rules

* `data` is always a list of Item DTOs
* `pagination` is always `PaginationDTO`
* No conditional response shapes

---

## 12.6 Action Feature – File Skeleton

### Example

```
POST /api/roles/{id}/permissions/assign
```

### Required Files

```
Domain/
└── Contracts/
    └── Roles/
        └── RolePermissionsRepositoryInterface.php

Infrastructure/
└── Repository/
    └── Roles/
        └── PdoRolePermissionsRepository.php

Validation/
└── Schemas/
    └── Roles/
        └── RolePermissionAssignSchema.php

Http/
└── Controllers/
    └── Api/
        └── Roles/
            └── RolePermissionAssignController.php
```

---

## 12.7 UI Page Skeleton (With Tabs)

### Twig

```
templates/pages/roles/role-details.twig
```

Contains:

* Header
* Tabs container
* Empty tab bodies

No data rendering.

---

### JS Modules

```
public/admin/assets/js/pages/
├── role-details-tabs.js
├── role-details-permissions.js
└── role-details-admins.js
```

### Responsibilities

| File             | Responsibility          |
|------------------|-------------------------|
| `tabs.js`        | Tab switching only      |
| `permissions.js` | Permissions API + table |
| `admins.js`      | Admins API + table      |

Each file is **fully independent**.

---

## 12.8 Capability Injection Contract

### Backend

UI controller injects:

```php
'capabilities' => [
    'can_view_permissions'   => bool,
    'can_assign_permissions' => bool,
    'can_view_admins'        => bool,
]
```

### Frontend

Frontend **must**:

* Read only from `capabilities`
* Never infer permissions
* Never hardcode access rules

---

## 12.9 Feature Validation Rule

A feature is **invalid** if:

* Pagination is not used for growing data
* DTOs are skipped
* Filters are manually parsed
* One tab depends on another
* UI makes permission decisions

---

## Final Enforcement

This skeleton is **not optional**.

Every new feature must:

* Match this structure
* Use these file locations
* Follow these responsibilities

Anything else is rejected by definition.

---

## Final Statement

This document defines **how the system is built**.

It is not a suggestion.
It is not an example.
It is the execution law of the project.

Any implementation that deviates from this document
is considered incorrect by design.
