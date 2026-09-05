# PACKAGE_BUILDING_STANDARD

**Maatify Standalone Composer Package Building Standard — v1**
This document is the law for building any new standalone Composer package in the Maatify ecosystem.
Read it fully before writing a single line of code.

---

## 1. The Package Contract

This standard applies generally to all PHP/Composer reusable libraries in the Maatify ecosystem.

Every package must be:

- **Standalone** — runs isolated from host applications (depends only on explicit Composer/runtime dependencies) with no knowledge of the host project internals
- **Installable** — packaged as a `composer require` library
- **Host-agnostic** — never FKs or JOINs on host tables. Host provides IDs; package trusts them. Host applications wire dependencies themselves.
- **PHPStan max** — zero errors at level max before the package is considered done

### Persistence Conditional Applicability

All rules in this Standard concerning PDO, SQL, `schema/`, migrations, transaction handling, Ordering/Pagination, PDO hydration, database integration, and database-focused testing apply only when the package owns persistence or database behavior.

A package without persistence or database behavior is not required to use PDO, provide `schema/`, define migrations, or implement database-specific architecture or tests. When a package does own persistence or database behavior, all applicable persistence requirements in this Standard remain mandatory, including direct PDO usage, no ORM, and no external query builder. Small internal SQL fragment builders are allowed only for repeated package-local query logic.

### Scope and Ownership

- This file owns runtime architecture, package structure, and test architecture.
- [`COMPOSER_PACKAGE_STANDARD.md`](COMPOSER_PACKAGE_STANDARD.md) owns `composer.json`, dependency declarations, and version constraints.
- [`CI_WORKFLOW_STANDARD.md`](CI_WORKFLOW_STANDARD.md) owns workflow and check execution.
- [`LIBRARY_PRESENTATION_STANDARD.md`](LIBRARY_PRESENTATION_STANDARD.md) owns README structure, badges, and release-facing files.

These standards MUST use cross-references and MUST NOT duplicate each other's detailed rules.

---

## 2. Required Maatify Runtime Dependencies

To maintain standalone Composer package boundaries and a framework-agnostic architecture while ensuring ecosystem consistency, packages must rely on the provided Maatify shared packages rather than defining package-local duplicates.

- **Exceptions:** When a package defines package-owned exception classes, they MUST use the appropriate stable hierarchy from `maatify/exceptions`, available from `v1.0.0`.
  - `MaatifyException` is the abstract root base class.
  - `ApiAwareExceptionInterface` is the general public contract.
  - The package marker interface remains package-owned and extends `\Throwable`.
  Repository: https://github.com/Maatify/exceptions

- **Clock/Date-Time:** A package that requires a clock abstraction MUST depend on `maatify/shared-common` and consume `ClockInterface`, available from `v1.0.0`. `SystemClock` is a production implementation available from `v1.0.0`, but it is not mandatory for every consumer. No shared Frozen/Test Clock is claimed by this Standard.
  Repository: https://github.com/Maatify/SharedCommon

- **Persistence Utilities:** Packages that require reusable PDO row-position/display-order management (available from `v1.0.0`) or pagination capabilities (available from `v1.1.0`) must depend on `maatify/persistence`.
  Repository: https://github.com/Maatify/persistence

**Rule:** Packages MUST NOT define package-local duplicates of a capability that is available through a stable public API in a Maatify shared package. This prohibition includes exception hierarchies, clock abstractions, PDO row-position/display-order mechanics, and PDO pagination mechanics.

A consuming package MUST declare the minimum stable dependency version that exposes every shared API it uses. Proposed architecture documents, unreleased branches, commits, and implementation contracts are not consumable public APIs and MUST NOT be copied into consumer packages.

The declaration, constraint, ordering, and validation rules for these dependencies are governed by [COMPOSER_PACKAGE_STANDARD.md](COMPOSER_PACKAGE_STANDARD.md).

This ensures:
- Explicit Composer/runtime dependencies only
- Public API stability
- Backward compatibility as much as possible
- One authoritative implementation for shared ecosystem capabilities

---

## 3. Required Files

Repository presentation, governance-document identity, release-facing metadata, and visual consistency MUST follow [LIBRARY_PRESENTATION_STANDARD.md](LIBRARY_PRESENTATION_STANDARD.md).
Composer package metadata, dependency declarations, autoloading, scripts, configuration, stability, and lock-file policy MUST follow [COMPOSER_PACKAGE_STANDARD.md](COMPOSER_PACKAGE_STANDARD.md).


Every package must contain these files at its root (the repository root is the package root):

```
├── README.md                          ← installation, quick examples, what it does / does not
├── CHANGELOG.md                       ← Keep a Changelog; release history begins at [1.0.0]
├── {PACKAGE_NAME}_PACKAGE_REFERENCE.md ← canonical stable package contract (e.g. EXAMPLE_PACKAGE_REFERENCE.md)
├── composer.json                      ← governed by COMPOSER_PACKAGE_STANDARD.md
├── phpstan.neon                       ← governed by Section 21
├── src/                               ← all PHP source code
├── tests/                             ← if applicable
├── schema/                            ← if applicable (only when the package has persistence or database behavior)
└── docs/                              ← detailed architecture, integration, roadmap, and audit documents
```

### Canonical Package Reference Location

Every standalone Maatify package MUST maintain exactly one canonical Package Reference at the repository root.

The canonical path is:

```text
/{PACKAGE_NAME}_PACKAGE_REFERENCE.md
```

The root Package Reference owns:

- the complete stable package contract
- the public Runtime API inventory
- stable behavior and exception guarantees
- package boundaries and non-goals
- links to detailed supporting documentation

Detailed architecture decisions, proposed implementation contracts, integration guides, roadmaps, deferred scope, and audits belong under `docs/`.

Supporting documents under `docs/` MAY provide deeper detail, but they MUST NOT become a second competing Package Reference. They SHOULD link back to the root Package Reference when they define or explain part of the stable package contract.

The root Package Reference SHOULD index the relevant detailed documents under `docs/`.

A multi-domain package still has one canonical root Package Reference unless a domain is extracted into a separate Composer package with its own repository and package contract.

---

## 4. Namespace

Pattern: `Maatify\{PackageName}\`

```
Maatify\LibraryExample\
Maatify\{NextPackage}\
```

*Note: Host app namespaces such as `App\`, `HostApp`, or `OtherApp` are strictly forbidden.*

### Type Naming Convention

Public and internal type names MUST make the declared artifact type immediately clear to implementers and reviewers.

The required suffixes are:

- every interface MUST end with `Interface`
- every enum MUST end with `Enum`
- every Data Transfer Object MUST end with `DTO`
- every exception class MUST end with `Exception`

Exception marker interfaces are not exempt from the interface rule. A new package marker MUST therefore use:

```text
{PackageName}ExceptionInterface
```

The PHP filename MUST match the declared type name.

New packages and unpublished APIs MUST NOT introduce alternative names that omit the required suffix. Package-specific compatibility exceptions are permitted only under the legacy-public-API rule in Section 7.

---

## 5. Directory Structure Inside `src/`

```
src/
├── {Domain}/                                        ← subpackage / domain boundary
│   ├── Exception/
│   ├── Contract/
│   ├── DTO/
│   ├── Infrastructure/Repository/
│   └── Service/
│
├── Common/                                          ← framework-neutral shared primitives only
│
├── Factory/                                         ← optional, only if framework-agnostic
│
└── Provider/                                        ← optional, only if framework-agnostic
```

- **No mandatory `Admin/Customer`**: Domain boundaries should reflect logical separation.
- **No mandatory `Bootstrap`**: Host apps are responsible for wiring.
- **No framework-specific ServiceProvider/Bindings**: E.g., no Laravel/Slim/PHP-DI bindings inside the package.

---

## 6. Schema Rules

These rules, including `schema/`, database structure, database tests, and migrations, are conditionally applicable **only** when the package has persistence or database behavior.

- Table prefix: `maa_{package_short_name}_` (e.g. `maa_library_example_`)
- Every table needs: `PRIMARY KEY (id)`, proper indexes, meaningful COMMENTs on columns.
- All policies (soft delete, display order, FK behavior, uniqueness) documented in the SQL header.
- Domain-local schema files are allowed (e.g. `src/{Domain}/Database/`).
- Package-level `schema/README.md` may index domain-local SQL files.
- **No generic shared `logs` or `event_logs` tables**; use strict domain-isolated tables.
- No FK constraints or JOINs to host app tables — use `COMMENT 'Host-provided ID. No FK.'`.

---

## 7. Exception Rules

### Standard Exception Types

1. Every package-defined exception MUST use the appropriate `maatify/exceptions` hierarchy.
2. Every package MUST expose a package marker interface extending `\Throwable`.
3. Stable, package-owned failure classifications SHOULD use named package exceptions.
4. A known domain or storage condition MAY be converted to a named package exception when the package owns a stable semantic classification.
5. Unknown or external `PDOException` / `Throwable` instances MAY propagate unchanged when preserving the original diagnostic contract is intentional.
6. A package MUST document whether it wraps or propagates infrastructure errors.
7. Blind catch-all wrapping is forbidden.
8. Swallowing errors is forbidden.
9. When wrapping, preserve the original throwable as `previous` where supported.
10. Transaction catch blocks must rollback owned transactions and rethrow the original throwable unless an explicitly documented semantic conversion is performed.
11. Rethrowing the original throwable after rollback is valid and is not a violation of named-exception rules.
12. Packages MUST NOT be universally required to convert every external infrastructure failure into a package-defined exception. The chosen wrapping or propagation contract must follow the package-owned semantic boundary and be documented.

### Interface

```php
interface {PackageName}ExceptionInterface extends \Throwable {}
```

The canonical package marker location is:

```text
Maatify\{PackageName}\Exception\{PackageName}ExceptionInterface
```

Every package-defined exception MUST implement this marker directly or indirectly. External `PDOException` or other propagated infrastructure throwables MUST NOT be forced to implement the package marker.

### Legacy Published Marker Names

A package that already published a stable marker interface without the required `Interface` suffix MAY retain that name when renaming it would require an otherwise unnecessary major release.

This exception:

- MUST be documented explicitly in the root Package Reference
- MUST identify the affected major release line
- MUST remain internally consistent throughout that major line
- MUST require every package-defined exception added during that major line to implement the retained marker directly or indirectly
- MUST NOT introduce a parallel marker solely to normalize naming
- MUST remain package-specific and MUST NOT be copied into new packages
- MUST be reconsidered only during a separately approved, meaningful future major release

A major release MUST NOT be created solely to rename a legacy marker.

### Example: Package-Defined Storage Exception

```php
final class {Domain}DatabaseException extends \Maatify\Exceptions\Exception\System\SystemMaatifyException
    implements {PackageName}ExceptionInterface
{
    // Implementation uses Maatify codes
}
```

Named constructors SHOULD be used when a stable semantic constructor exists.

Direct construction MAY be used when no suitable named constructor exists and the package's documented exception contract permits it.

Call sites MUST NOT construct generic or semantically misleading exceptions merely to avoid defining an appropriate package-owned classification.

### Fail-Open / Fail-Closed Behavior

Behavior must stay domain-specific:
- **Authoritative Domains** (e.g. `{AuthoritativeDomain}`) are fail-closed where required by the domain.
- **Non-Authoritative Domains** may fail-open only at an explicitly documented boundary.
- **Repositories and Read Queries** must never silently swallow storage failures.

### What the package catches and converts

- `PDOException::getCode()` represents the SQLSTATE, not a Driver-specific error code.
- SQLSTATE Class `23` is a broad category for Integrity Constraint Violations (e.g., Duplicate Key, Foreign Key, Explicit NULL), not just duplicates alone.
- It is forbidden to blindly map all `23xxx` errors to an `AlreadyExists` or Duplicate exception.
- Semantic conversion is permitted only when there is documented Driver-specific evidence proving a Duplicate Key violation.
- `PDOException::$errorInfo` might be `null`, so you must check for the existence of `[1]` safely before using it.
- The MySQL/MariaDB driver uses code `1062` to identify a Duplicate Key. Any other Driver or DBMS must use its own official documented code and must not blindly copy `1062`.
- Any error not explicitly classified by a documented evidence must propagate as-is.
- Retain the `previous` exception when your wrapping exception contract supports it, but do not invent a new named constructor signature solely for that purpose.

*Note: The following example is Driver-specific to MySQL/MariaDB. It is not a general rule for all PDO drivers.*

```php
} catch (\PDOException $e) {
    $driverCode = isset($e->errorInfo[1])
        ? (int) $e->errorInfo[1]
        : null;

    // MySQL/MariaDB duplicate-key driver code.
    if ($driverCode === 1062) {
        throw {Domain}CodeAlreadyExistsException::withCode($command->code);
    }

    throw $e;
}
```

### Transaction Pattern

In any method that uses `beginTransaction()`:

```php
        $this->pdo->beginTransaction();

        try {
            // ...
            $this->pdo->commit();
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            // Optional documented semantic conversion may occur here.

            throw $e;
        }
```

Only package-owned transactions are rolled back by this pattern.
Rollback must be attempted only while the transaction is active.
The original throwable is rethrown unless an explicitly documented semantic conversion is performed.
Swallowing the throwable is forbidden.
If a semantic conversion wraps the original throwable, the original should be retained as `previous` where supported.

## 8. Command Rules

Commands are self-validating value objects:

```php
final readonly class CreateSomethingCommand
{
    public function __construct(
        public string  $code,
        public string  $name,
        public bool    $isActive,
        public ?string $notes,
    ) {
        if (trim($code) === '') {
            throw SomethingInvalidArgumentException::emptyField('code');
        }
        if (trim($name) === '') {
            throw SomethingInvalidArgumentException::emptyField('name');
        }
    }
}
```

Rules:
- `final readonly` — always
- Validation only in the constructor — no business logic
- Display-order inputs MUST follow Section 16; `CreateCommand` and `UpdateCommand` do not accept `display_order`
- Image inputs MUST follow Section 17; `CreateCommand` and `UpdateCommand` do not accept `image`
- Host IDs (e.g. `methodId`, `currencyId`) must be validated with `>= 1` guard
- Monetary and fixed-precision decimal inputs MUST follow Section 19
- Date/time strings must be validated with `new \DateTimeImmutable($value)` in a try/catch

---

## 9. DTO Rules

```php
final readonly class SomethingDTO implements \JsonSerializable
{
    public function __construct(
        public int    $id,
        public string $name,
    ) {}

    public function jsonSerialize(): mixed
    {
        return ['id' => $this->id, 'name' => $this->name];
    }
}
```

- `final readonly` — always
- Implements `\JsonSerializable`
- Collection DTOs implement `\IteratorAggregate` + `\JsonSerializable`:

```php
/** @implements \IteratorAggregate<int, SomethingDTO> */
final readonly class SomethingCollectionDTO implements \IteratorAggregate, \JsonSerializable
{
    /** @var list<SomethingDTO> */
    private array $items;

    /** @param list<SomethingDTO> $items */
    public function __construct(array $items) { $this->items = $items; }

    /** @return \ArrayIterator<int, SomethingDTO> */
    public function getIterator(): \ArrayIterator { return new \ArrayIterator($this->items); }

    public function jsonSerialize(): mixed { return $this->items; }
}
```

---

## 10. Repository Rules

### Command Repository Return Types

| Operation | Returns | Why |
|---|---|---|
| `create()` | `int` | `lastInsertId()` |
| `update()` | `bool` | `rowCount() > 0` |
| `updateStatus()` | `bool` | `rowCount() > 0` |
| `updateDisplayOrder()` | `bool` | delegated to `Maatify\Persistence\Pdo\Ordering\ScopedOrderingManager` from `maatify/persistence` |
| `updateImage()` | `bool` | `rowCount() > 0` |
| `softDelete()` | `bool` | `rowCount() > 0` |
| `hardDelete()` | `bool` | `rowCount() > 0` after transaction |
| `findById()` | `?DTO` | `null` if not found — Service decides whether to throw |

This table defines Repository-level return contracts. A Service MAY expose `updateDisplayOrder(...): void`, call the Repository operation, and convert `false` into the approved not-found exception according to Section 13. The Repository and Service signatures belong to different layers and MUST NOT be treated as competing alternatives.

### `findById` Pattern

```php
// Repository — returns null, never throws for not-found
public function findById(int $id): ?SomeDTO
{
    $stmt = $this->pdo->prepare('SELECT ... FROM maa_something WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);

    /** @var array<string, mixed>|false $row */
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row === false) {
        return null;
    }

    return $this->hydrateDetail($row);
}

// Service — throws NotFoundException when null
public function getById(int $id): SomeDTO
{
    $dto = $this->queryReader->findById($id);

    if ($dto === null) {
        throw SomethingNotFoundException::withId($id);
    }

    return $dto;
}
```

### Hydration — never cast `mixed` directly

```php
// ❌ PHPStan max rejects this
isActive:     (bool) ($row['is_active']     ?? false),
displayOrder: (int)  ($row['display_order'] ?? 0),

// ✅ extract first, check type, then cast
$isActive     = $row['is_active']     ?? null;
$displayOrder = $row['display_order'] ?? null;

isActive:     (is_int($isActive)     || is_string($isActive)) && (int) $isActive === 1,
displayOrder: (is_int($displayOrder) || is_string($displayOrder)) ? (int) $displayOrder : 0,
```

---

## 11. Shared Ordering and Pagination Utilities

This section governs how standalone Maatify packages consume shared PDO ordering and pagination capabilities. It is a dependency and integration policy, not an implementation blueprint.

The authoritative owner is:

```text
Package:    maatify/persistence
Repository: https://github.com/Maatify/persistence
```

### Non-Duplication Rule

A consumer package MUST NOT create a local replacement for stable ordering or pagination behavior provided by `maatify/persistence`.

Forbidden duplication includes:

- ordering managers, position-shifting algorithms, scope-locking logic, and ordering transaction behavior
- page and per-page normalization
- total and filtered count orchestration
- pagination offset calculation
- whitelist-based sorting and deterministic tie-breaker behavior
- canonical pagination result objects and metadata
- package-local copies of persistence exceptions or public value objects

A consumer package MUST NOT copy Runtime code, tests, internal algorithms, or documentation-only contracts from `maatify/persistence`.

### Capability Availability and Composer Dependency

A package may consume only an API that exists in a stable published release of `maatify/persistence`.

The consumer MUST:

- declare `maatify/persistence` as a direct Composer dependency when its public API is referenced
- require the minimum stable version that contains the needed capability
- confirm availability from the published package reference and stable release documentation
- avoid depending on an unreleased branch, commit, proposed contract, or target version as though it were an available Runtime API

A documentation-only architecture contract inside `maatify/persistence` establishes ownership and future implementation rules. It does not authorize another package to implement or consume the proposed API before a stable release publishes it.

### Ordering Integration

In this section, `Ordering` means reusable row-position and display-order management exposed by `Maatify\Persistence\Pdo\Ordering`. It does not include ordinary consumer-owned read-query sorting expressed through `ORDER BY`.

Reusable row-position and display-order operations covered by the stable `maatify/persistence` Ordering API MUST use:

```text
Maatify\Persistence\Pdo\Ordering
```

The consumer Repository / Service may:

- provide the PDO connection
- construct trusted ordering configuration from its own table and column identifiers
- pass domain ids, scope values, and requested positions
- translate the stable persistence result into its own existing public contract through a thin adapter

The consumer MUST NOT reproduce ordering SQL shifts, transaction ownership, scope locking, identifier validation, or persistence exception behavior locally.

**Integration Boundaries:**
- `getNextPosition()` does not start a transaction or acquire a lock. When concurrency correctness is required, the caller owns both the transaction and the appropriate locking strategy. It alone returns `MAX + 1`.
- `moveWithinScope()` owns a transaction and refuses to run in an active PDO transaction.
- Non-positive movement values are rejected before clamping. Clamping is to the maximum existing position.
- Soft-delete filtering is supported when `deletedAtColumn` is configured and disabled when it is `null`.
- Do not duplicate full public signatures; refer to `maatify/persistence`.

### Pagination Integration

Paginated Repository / QueryReader operations MUST delegate reusable pagination mechanics to the stable public API under:

```text
Maatify\Persistence\Pdo\Pagination
```

`PdoPaginator` does not own a transaction and can run inside a caller-owned transaction without modifying its state.

The consumer remains responsible for domain-owned concerns:

- mandatory security, tenant, ownership, visibility, and soft-delete constraints
- optional search and filter construction
- JOINs and selected columns
- trusted SQL and matching parameter values
- semantic alignment between count and data queries
- row mapping into the consumer's array or DTO
- preserving its own existing public response contract

`maatify/persistence` owns the reusable mechanics exposed by its stable API, including normalization, count execution, deterministic sorting, limit/offset handling, mapper invocation, and canonical pagination metadata.

Adopting the shared paginator MUST NOT silently change an existing endpoint, DTO, or package return shape. A thin consumer-owned adapter MAY preserve an established public contract.

### Thin Adapters

A consumer-owned adapter is permitted only when it:

- translates domain inputs into the stable `maatify/persistence` API
- delegates the shared operation without reimplementing its algorithms
- maps the returned result into an already approved consumer contract
- adds no competing exception hierarchy, pagination engine, or ordering engine

An adapter MUST NOT become a fork of the shared capability.

### Missing Capability

When a required reusable behavior is absent from the stable public API, the default action is to propose and review the capability in `maatify/persistence`.

A package-local alternative is forbidden unless an explicit owner-approved architectural decision proves that the behavior is domain-specific and outside the shared package's scope.

Temporary copying of an unpublished persistence contract is not an acceptable workaround.

### Source of Truth

Consumer packages MUST follow the stable public API, package reference, and published integration documentation in `maatify/persistence`.

Exact class signatures, internal formulas, SQL assembly rules, exception classifications, and verification requirements belong in the owning repository. They MUST NOT be duplicated in this general package-building standard or independently redefined by consumer packages.

---

## 12. Translation Pattern

### Always support both paths

```php
// Path 1: with translation (for apps using a translation system)
listByLanguageId(int $languageId): CollectionDTO

// Path 2: base name only (for apps without a translation system)
listWithoutTranslation(): CollectionDTO
```

### JOIN Guard — critical for avoiding duplicate rows

```php
// ONLY join translations when languageId is explicitly provided.
// Without this guard: if a method has 2 translations (ar + en),
// a JOIN without language filter returns 2 rows per method.

if ($languageId !== null) {
    $joinSql           = 'LEFT JOIN maa_something_translations t
                              ON t.something_id = s.id
                             AND t.language_id  = :language_id';
    $translationSelect = 'COALESCE(t.name,  s.name)  AS name,
                          COALESCE(t.image, s.image) AS image';
    $params['language_id'] = $languageId;
} else {
    $joinSql           = '';
    $translationSelect = 'NULL AS translated_name, NULL AS translated_image';
}
```

### COALESCE Fallback Chain

```sql
-- Always in customer queries:
COALESCE(t.name,  s.name)  AS name    -- translated → base
COALESCE(t.image, s.image) AS image   -- translated → base

-- Admin findById (no language): select s.name directly — no JOIN needed
```

### Upsert Pattern — always, never separate INSERT + UPDATE

```sql
INSERT INTO maa_something_translations
    (something_id, language_id, name)
VALUES
    (:something_id, :language_id, :name)
ON DUPLICATE KEY UPDATE
    name       = VALUES(name),
    updated_at = NOW()
```

Requires `UNIQUE KEY (something_id, language_id)` on the translation table.

### Admin Translation List — LEFT JOIN on base table

```sql
SELECT
    t.id,
    t.something_id,
    t.language_id,
    t.name,
    t.image,
    t.created_at,
    t.updated_at,
    s.name  AS base_name,    -- shown alongside translation for admin comparison
    s.image AS base_image
FROM maa_something_translations t
LEFT JOIN maa_something s ON s.id = t.something_id
{$whereSql}
ORDER BY t.something_id ASC, t.language_id ASC
```

### Global Search Scope

| Context | Search fields |
|---|---|
| Admin main list | Base table fields only (`s.name`, `s.code`) — never join translations for search |
| Admin translation list | Translation fields only (`t.name`) — that IS the translation table |
| Customer list | No search — customer receives a filtered, ordered list only |

---

## 13. Service Rules

Responsibility:
- **Business orchestration** lives in Services
- **Validation** lives in Commands and DTO filters
- **SQL** lives in Repositories or package-local/domain-local SQL support builders

```php
// Command service — throws NotFoundException when repo returns false
public function update(UpdateSomethingCommand $command): void
{
    $updated = $this->commandRepo->update($command);
    if (! $updated) {
        throw SomethingNotFoundException::withId($command->id);
    }
}

// Query service — throws NotFoundException when repo returns null
public function getById(int $id): SomethingDTO
{
    $dto = $this->queryReader->findById($id);
    if ($dto === null) {
        throw SomethingNotFoundException::withId($id);
    }
    return $dto;
}
```

Services **never**:
- Contain raw SQL
- Catch exceptions (let them propagate)
- Instantiate repositories directly (use constructor injection)

---

## 14. Admin vs Customer Separation

Some business modules may choose actor-specific namespaces (e.g., `Admin\` vs `Customer\`).

However, infrastructure packages such as `{PACKAGE_SLUG}` should use their actual package/domain boundaries instead. For example, `{DomainOne}` and `{DomainTwo}` may serve as public architectural boundaries rather than arbitrary Admin/Customer folders.

---

## 15. Read / Admin Query API Rules

Packages that have persisted data intended to be viewed, searched, audited, monitored, or reported by host applications should expose framework-agnostic PHP read/query contracts where applicable.

**Important:** This refers strictly to **PHP-level APIs** (e.g., PHP interfaces and DTOs), not HTTP APIs.

These contracts may cover (where applicable and appropriate for the package's domain):
- Admin listing
- Search
- Dashboard summaries
- Reporting summaries

**Explicitly forbidden inside the package:**
- HTTP controllers
- Routes
- Middleware
- Permissions
- UI dashboards
- CSV/PDF/Excel exports
- Host-specific actor/name resolution
- JOINs/FKs on host tables

Remember to uphold the core principles: maintain a standalone, framework-agnostic, and host-agnostic architecture, prioritize domain boundaries over mandatory Admin/Customer folder structures, and ensure all public query capabilities have matching PHP contracts/interfaces.

---

## 16. display_order Rules

When persisted entities expose mutable row-position or display-order behavior, the package MUST consume the stable public Ordering API from `maatify/persistence`.

The package-level integration contract is:

- `display_order` MUST NOT be accepted by `CreateCommand` or `UpdateCommand`
- creation-time position assignment MUST delegate to the stable Ordering API when automatic assignment applies
- movement MUST be exposed through a dedicated package operation rather than a generic update command
- the Command Repository operation MUST return `bool` to report whether the target row existed and was moved
- a Service MAY expose a `void` operation and convert a Repository `false` result into the approved not-found exception
- shifting, clamping, scope locking, transaction ownership, identifier validation, and persistence exception behavior MUST remain delegated to `maatify/persistence`
- a consumer MUST NOT reproduce or fork the Ordering engine locally

Exact class names, method signatures, transaction behavior, and Runtime semantics are owned by the stable `maatify/persistence` public API and [PERSISTENCE_PACKAGE_REFERENCE.md](https://github.com/Maatify/persistence/blob/main/PERSISTENCE_PACKAGE_REFERENCE.md).

### Deferred Hard-Delete Ordering Compaction

The stable `maatify/persistence` Ordering API currently does not expose a hard-delete compaction operation.

The package-specific decision to preserve scoped ordering compaction as a future candidate is recorded in [ADR 0002 — Ordering Hard-Delete Compaction](https://github.com/Maatify/persistence/blob/main/docs/adr/0002-ordering-hard-delete-compaction.md). Its status is `Accepted — Deferred`. Compaction is not implemented, has no stable API or release target, and is not part of Pagination `v1.1.0`.

Until a stable Runtime API is separately approved, implemented, released, and recorded in [PERSISTENCE_PACKAGE_REFERENCE.md](https://github.com/Maatify/persistence/blob/main/PERSISTENCE_PACKAGE_REFERENCE.md):

- consuming projects retain ownership of entity deletion and project-specific hard-delete orchestration
- consumers MUST NOT claim or depend on an unreleased Persistence compaction API
- this Standard does not prescribe a method signature, locking strategy, transaction-participation contract, or release target for the deferred capability
- the deferred decision MUST NOT be treated as an expansion of the `v1.1.0` Pagination scope

---

## 17. Image Rules

- An `image` column is `VARCHAR(255) NULL` and stores a path or URL only, never binary data
- `image` MUST NOT be accepted by `CreateCommand` or `UpdateCommand`
- image changes MUST be exposed through dedicated package operations rather than generic update commands
- the Command Repository image-update operation MUST return `bool` to report whether the target row existed and was updated
- a Service MAY expose a `void` image-update operation and convert a Repository `false` result into the approved not-found exception
- `null` clears the image by setting the column to `NULL`
- translation-image updates MUST follow the same Repository `bool` / Service `void` layer contract

---

## 18. Bootstrap / DI Rules

**Composer packages must not require host-specific bindings.**

Rules:
- No Slim/Laravel/Symfony/PHP-DI bindings as package requirements.
- Optional factories or providers are allowed, but they must be strictly framework-agnostic.
- Host applications are fully responsible for wiring dependencies through their own container or runtime environment.

---

## 19. Decimal / Financial Rules

- All monetary values stored as `string` (DECIMAL precision — never `float`)
- All arithmetic uses `bcmath` — never native PHP arithmetic on monetary values
- Validate decimal format **before** any `bcmath` call:

```php
if (! preg_match('/^\d+(?:\.\d{1,4})?$/', $value)) {
    throw SomethingInvalidArgumentException::invalidDecimal($field, $value);
}
```

- Always pass explicit scale: `bcadd($a, $b, 4)`, `bcmul($a, $b, 4)`, `bcdiv($a, $b, 8)`

---

## 20. PDO Named Placeholder Rule

PDO does not reliably support the same named placeholder more than once per statement.
Every placeholder must appear exactly once per SQL string.

When the same value is needed in multiple subqueries:

```php
// ❌ WRONG — same placeholder used twice
AND gc.country_code = :country_code  -- subquery 1
AND gc.country_code = :country_code  -- subquery 2

// ✅ CORRECT — unique names, same value
AND gc_al.country_code = :country_code_allow   -- allowlist subquery
AND gc_bl.country_code = :country_code_block   -- blocklist subquery

$params['country_code_allow'] = $countryCode;
$params['country_code_block'] = $countryCode;
```

---

## 21. PHPStan and Testing Rules

### `phpstan.neon`

```neon
parameters:
    level: max
    paths:
        - src
```

The `src` path is mandatory.

When a `tests/` directory exists, it MUST also be included in `parameters.paths`:

```neon
parameters:
    paths:
        - src
        - tests
```

Repositories MUST configure PHPStan from their actual package-owned paths and MUST NOT reference a non-existent path merely to copy this example. Existing package-owned tests MUST NOT be excluded from static analysis.

### Final Classes and Test Doubles

- Test Doubles MUST target interfaces or replaceable contracts by default, not concrete `final` classes.
- A `final` keyword MUST NOT be removed from a class solely to facilitate testing.
- When testing a class that depends on a concrete final class:
  1. Use a real instance if it is deterministic and suitable for testing.
  2. Or test via a Mock/Fake for the collaborators or external boundaries that it depends on.
  3. Create an interface only when replaceability is a true runtime requirement, not just to satisfy a single test.
- The package `dg/bypass-finals` is NOT a Central Baseline and NOT a general requirement for Maatify projects.
- Its continued use in a Legacy project — if any — is a documented local decision for that project, and MUST NOT be copied as a central policy.
- It is strictly forbidden to add the following solely to allow mocking a `final` class:
  - `ignoreErrors`
  - PHPStan baseline
  - inline suppression
  - `@phpstan-ignore method.unresolvableReturnType`
- Do not claim that every concrete class must have an interface; an interface is required only when there is a real replaceable capability.

### Testing Strategy

- Packages that own persistence, database, or external-service behavior MUST define appropriate Integration coverage. Unit and Regression suites remain required where applicable.
- Package-owned test behavior, fixtures, and suite responsibilities belong to the package architecture and reference documentation.
- CI execution requirements — including real-service provisioning, MySQL/SQLite enforcement, PHP matrices, cleanup/repeatability checks, and example syntax validation — are governed exclusively by [`CI_WORKFLOW_STANDARD.md`](CI_WORKFLOW_STANDARD.md).

### PDO fetch results — always annotate

```php
/** @var array<string, mixed>|false $row */
$row = $stmt->fetch(PDO::FETCH_ASSOC);

/** @var list<array<string, mixed>> $rows */
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
```

### DTO and Hydration Annotations

Collection generics and list annotations are governed by Section 9. PDO-row hydration and mixed-value extraction rules are governed by Section 10. This section MUST NOT redefine those contracts.

### LIMIT / OFFSET — always PDO::PARAM_INT

```php
// ❌ PDO binds as string by default
$stmt->bindValue(':limit', $limit);

// ✅ explicit type
$stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
```

### Private method annotations

```php
/** @param array<string, mixed> $row */
private function hydrateListItem(array $row): ?SomeListItemDTO

/** @param array<string, mixed> $row */
private function hydrateDetail(array $row): ?SomeDTO

/**
 * @param  list<array<string, mixed>>  $rows
 * @return list<SomeDTO>
 */
private function hydrateAll(array $rows): array

/** @return list<string> */
private function fetchRelatedItems(int $parentId): array

/** @return array<string, mixed>|null */
private function findRawById(int $id): ?array
```

---

## 22. CI Workflow Rules

Every new standalone Composer package in the Maatify ecosystem must adhere to the CI workflow standards defined in [`CI_WORKFLOW_STANDARD.md`](CI_WORKFLOW_STANDARD.md).

`CI_WORKFLOW_STANDARD.md` is the single detailed source of truth for workflow architecture, path relevance, dependency resolution, PHP compatibility matrices, quality checks, real-service Integration execution, security audit, workflow linting, permissions, immutable action pinning, execution reliability, and stable required gates.

Compliance requires the repository's CI to pass the current Compliance Checklist defined by that Standard. This Package Building Standard MUST NOT independently redefine those CI mechanics.

---

## 23. The Package Is NOT Done Until

- [ ] CI workflows exist, pass, and satisfy the current Compliance Checklist in `CI_WORKFLOW_STANDARD.md`
- [ ] Package-owned runtime and test architecture is represented in CI where applicable
- [ ] `README.md` written with installation steps and quick examples
- [ ] `CHANGELOG.md` follows `LIBRARY_PRESENTATION_STANDARD.md`, retains `[Unreleased]` at the top, and begins release history at `[1.0.0]`
- [ ] `{PACKAGE}_PACKAGE_REFERENCE.md` complete — full API, design rules, extension guide
- [ ] `composer.json` complies with [COMPOSER_PACKAGE_STANDARD.md](COMPOSER_PACKAGE_STANDARD.md).
- [ ] Every public service/repository capability intended for infrastructure substitution has a matching contract (interface)
- [ ] Domain-specific failure semantics are documented
- [ ] Transaction catch blocks rethrow the original `\Throwable` after rollback — never swallow
- [ ] Business orchestration lives in Services, validation in Commands/filters, SQL in Repositories
- [ ] Schema docs align with MySQL/domain-owned tables, no generic `logs` or `event_logs` tables
- [ ] Framework-agnostic boundaries preserved: no host app namespaces, no framework bindings required
- [ ] No generic logger, recorder, or repository
- [ ] Docs reflect current exception rules, package-defined exceptions use `maatify/exceptions`, and any clock/date-time contract uses `maatify/shared-common` instead of a local duplicate
