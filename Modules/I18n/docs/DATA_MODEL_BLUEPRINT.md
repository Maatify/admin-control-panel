# I18n Data Model Blueprint

**Status:** DRAFT
**Module:** I18n
**Author:** Architecture Agent
**Date:** 2026-02-04

---

## 1. CURRENT STATE ANALYSIS

### 1.1 Existing Schema
The current `i18n_keys` table relies on a single, flat string column:

```sql
CREATE TABLE i18n_keys (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    translation_key VARCHAR(191) NOT NULL,
    description VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_i18n_keys_key (translation_key)
);
```

### 1.2 Limitations
1.  **Implicit Structure:** The code assumes dot-notation (e.g., `auth.login.title`), but the database does not enforce it. Keys like `error` or `auth..title` are valid but structurally broken.
2.  **Weak Querying:** Fetching all keys for the `auth` domain requires `LIKE 'auth.%'`, which is inefficient and prone to matching false positives (e.g., `authorization`).
3.  **Fragile Scoping:** There is no distinct separation between `admin` (ad), `client` (ct), or `system` (sys) keys, leading to potential leakage of admin keys into client contexts.
4.  **Namespace Collisions:** Without explicit structure, `page.title` in `home` might collide with `page.title` in `dashboard` if prefixes are inconsistent.

---

## 2. TARGET DATA MODEL

The new model strictly enforces a 3-part hierarchy: **Scope**, **Domain**, and **Key Part**.

### 2.1 Table Schema: `i18n_keys`

| Column Name | Type | Nullable | Purpose | Example |
| :--- | :--- | :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED` | NO | Primary Key (Immutable Identity) | `101` |
| `scope` | `VARCHAR(32)` | NO | High-level context (app, admin, sys) | `ct` |
| `domain` | `VARCHAR(64)` | NO | Functional grouping | `auth` |
| `key_part` | `VARCHAR(128)` | NO | Specific element identifier | `login.title` |
| `description` | `VARCHAR(255)` | YES | Developer/Admin context | `Login page header` |
| `created_at` | `DATETIME` | NO | Audit timestamp | `2026-01-01` |

### 2.2 Constraints
1.  **Primary Key:** `PRIMARY KEY (id)`
2.  **Uniqueness:** `UNIQUE KEY uq_scope_domain_key (scope, domain, key_part)`
    -   Prevents duplicates at the structural level.
    -   Enforces clean separation.

### 2.3 Field Design Decisions
*   **Scope:** `VARCHAR` (not ENUM) to allow future scopes (e.g., `driver`, `partner`) without DDL changes. However, application logic should strictly validate against known constants.
    *   `ct`: Customer / Client App
    *   `ad`: Admin Panel
    *   `sys`: System / Backend / Emails
    *   `api`: API-specific messages
*   **Domain:** `VARCHAR` to support evolving features (`products`, `orders`, `profile`).
*   **Key Part:** `VARCHAR` allows dot-notation *within* the leaf key if necessary (e.g., `form.email.label`), but strictly effectively "names" the item within the domain.

### 2.4 Virtual/Derived Full Key
The application layer (DTOs) will derive the full string as:
`{scope}.{domain}.{key_part}`

*Example:* `ct.auth.login.title`
*   Scope: `ct`
*   Domain: `auth`
*   Key Part: `login.title`

**Note:** The full string is **NOT** stored. This eliminates redundancy and ensures the structure is the source of truth.

---

## 3. DTO BLUEPRINT (NO CODE)

To support this model, DTOs must evolve from simple strings to structured objects.

### 3.1 `TranslationKeyIdentityDTO`
Represents the pure structure, used for lookups and creation.
*   `scope`: string (non-empty)
*   `domain`: string (non-empty)
*   `keyPart`: string (non-empty)
*   *Derived Property:* `fullKey` (getter combining parts)

### 3.2 `TranslationKeyRecordDTO`
Represents a persisted row.
*   `id`: int
*   `identity`: `TranslationKeyIdentityDTO` (embedded or flattened)
*   `description`: ?string
*   `createdAt`: string

### 3.3 Invariants
*   Scopes must be lowercase alphanumeric.
*   Domains must be lowercase alphanumeric (dashes allowed).
*   Key parts can contain dots but no leading/trailing dots.

---

## 4. REPOSITORY CONTRACT BOUNDARIES

The Repository Interface must shift from string-based to structure-based operations.

### 4.1 Read Responsibilities
*   **`getByIdentity(string $scope, string $domain, string $keyPart): ?TranslationKeyRecordDTO`**
    *   Canonical lookup method.
    *   Replaces `getByKey(string $key)`.
*   **`listByScope(string $scope): TranslationKeyCollectionDTO`**
    *   Optimized filtering.
*   **`listByDomain(string $scope, string $domain): TranslationKeyCollectionDTO`**
    *   Optimized filtering for specific feature sets.

### 4.2 Write Responsibilities
*   **`create(TranslationKeyIdentityDTO $identity, ?string $description): int`**
    *   Accepts structured data.
    *   Throws exception on duplicate (unique constraint violation).

### 4.3 Explicit NON-Responsibilities
*   **Parsing:** The Repository does **NOT** parse dot-notation strings. The Service layer is responsible for converting a string like `ct.auth.title` into a `TranslationKeyIdentityDTO` before calling the repository.
*   **Validation:** Basic type safety only. Business rules (e.g., "invalid scope") belong in the Service/Domain layer.

---

## 5. MIGRATION STRATEGY

Migrating existing unstructured data is the critical path.

### 5.1 Migration Logic (One-Time Script)
1.  **Read** all existing rows from `i18n_keys`.
2.  **Parse** `translation_key` by splitting on `.` (dot).
3.  **Map** parts to `scope`, `domain`, `key_part`:
    *   *Case A (3+ parts):* `part[0]` -> scope, `part[1]` -> domain, `rest` -> key_part.
    *   *Case B (2 parts):* `sys` (default) -> scope, `part[0]` -> domain, `part[1]` -> key_part.
    *   *Case C (1 part):* `sys` -> scope, `common` -> domain, `part[0]` -> key_part.
4.  **Insert** into new structure (or temp table) to verify uniqueness.
5.  **Drop** old `translation_key` column.
6.  **Rename** columns or finalize table structure.

### 5.2 Backward Compatibility
*   The **Service Layer** will momentarily maintain a `getByKey(string $fullKey)` method that parses the string and calls `getByIdentity`.
*   This ensures consumers (e.g., Blade templates using `{{ __('ct.home.title') }}`) do not break immediately.

---

## 6. NON-GOALS (LOCKED)

The following are explicitly **out of scope** for this data model work:

1.  **UI Implementation:** No changes to the Admin Panel UI are proposed here.
2.  **Caching:** Redis/Memcached strategies are separate from the persistent data model.
3.  **Translation Values:** `i18n_translations` table remains unchanged (it links to `key_id`).
4.  **Service Refactoring:** While Services must adapt to call the new Repository methods, complete Service redesign is not the goal.

---

## 7. READINESS CHECKLIST

Before applying implementation patches, ensure:

- [ ] **Schema SQL:** Prepared `ALTER TABLE` or `CREATE TABLE` scripts with strict types.
- [ ] **DTO Definitions:** PHP classes created for `TranslationKeyIdentityDTO`.
- [ ] **Parser Utility:** A helper (in Service layer) to robustly split strings into Identity DTOs.
- [ ] **Repository Interface:** Updated to accept `scope`, `domain`, `key_part`.
- [ ] **Migration Script:** Tested against a dump of production keys (if any) or seed data.
- [ ] **Service Adapter:** A shim to keep `getByKey(string)` working by parsing internally.
