# PACKAGE_BUILDING_STANDARD

**Maatify Standalone Composer Package Building Standard — v1**
This document is the law for building any new standalone Composer package in the Maatify ecosystem.
Read it fully before writing a single line of code.

---

## 1. The Package Contract

Every package must be:

- **Standalone** — runs isolated from host applications (depends only on explicit Composer/runtime dependencies) with no knowledge of the host project internals
- **Installable** — packaged as a `composer require` library
- **Host-agnostic** — never FKs or JOINs on host tables. Host provides IDs; package trusts them. Host applications wire dependencies themselves.
- **PDO-based** — all persistence uses PDO directly. No ORM, no external query builder.
  Small internal SQL fragment builders are allowed only for repeated package-local query logic.
- **PHPStan max** — zero errors at level max before the package is considered done

---

## 2. Required Maatify Runtime Dependencies

To maintain standalone Composer package boundaries and a framework-agnostic architecture while ensuring ecosystem consistency, packages must rely on the provided Maatify shared packages rather than defining package-local duplicates.

- **Exceptions:** Package exceptions must depend on `maatify/exceptions`
  Repository: https://github.com/Maatify/exceptions

- **Clock/Date-Time:** Clock and date-time contracts must depend on `maatify/shared-common`
  Repository: https://github.com/Maatify/SharedCommon

**Rule:** Packages must not define a local duplicate exception hierarchy or local clock abstraction if the contract or base is available in Maatify shared packages.

This ensures:
- Explicit Composer/runtime dependencies only
- Public API stability
- Backward compatibility as much as possible

---

## 3. Required Files

Every package must contain these files at its root (the repository root is the package root):

```
├── README.md                          ← installation, quick examples, what it does / does not
├── CHANGELOG.md                       ← versioned history, starting at [1.0.0]
├── {PACKAGE_NAME}_PACKAGE_REFERENCE.md ← complete API reference and design rules (e.g. docs/EVENT_LOGGING_MODULE_REFERENCE.md)
├── composer.json                      ← library type, psr-4 autoload, explicit runtime/Composer dependencies only
├── phpstan.neon                       ← level: max, paths: [src, tests]
├── src/                               ← all PHP source code
├── tests/                             ← if applicable
├── schema/                            ← if the package owns SQL schema
└── docs/                              ← for architecture/integration/audits
```

---

## 4. Namespace

Pattern: `Maatify\{PackageName}\`

```
Maatify\EventLogging\
Maatify\{NextPackage}\
```

*Note: Host app namespaces such as `App\`, `Athar`, or `EP4N` are strictly forbidden.*

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

- Allowed when the package owns persistence.
- Table prefix: `maa_{package_short_name}_` (e.g. `maa_event_logging_`)
- Every table needs: `PRIMARY KEY (id)`, proper indexes, meaningful COMMENTs on columns.
- All policies (soft delete, display order, FK behavior, uniqueness) documented in the SQL header.
- Domain-local schema files are allowed (e.g. `src/{Domain}/Database/`).
- Package-level `schema/README.md` may index domain-local SQL files.
- **No generic shared `logs` or `event_logs` tables**; use strict domain-isolated tables.
- No FK constraints or JOINs to host app tables — use `COMMENT 'Host-provided ID. No FK.'`.

---

## 7. Exception Rules

### Standard Exception Types

Package exceptions must use the Maatify exception hierarchy where applicable.

Storage/infrastructure exceptions in this package must be domain-specific and extend:
`Maatify\Exceptions\Exception\System\SystemMaatifyException`

They must use the appropriate Maatify error code enum, e.g.:
`ErrorCodeEnum::DATABASE_CONNECTION_FAILED`

*Note: `\RuntimeException` is explicitly **forbidden** as the base for storage exceptions.*

### Interface

```php
interface {PackageName}ExceptionInterface extends \Throwable {}
```

### Extending SystemMaatifyException

```php
final class {Domain}DatabaseException extends \Maatify\Exceptions\Exception\System\SystemMaatifyException
    implements {PackageName}ExceptionInterface
{
    // Implementation uses Maatify codes
}
```

Named constructors should remain recommended/required where applicable — never `new SomeException('...')` at call site.

### Fail-Open / Fail-Closed Behavior

Behavior must stay domain-specific:
- **Authoritative Domains** (e.g. `AuthoritativeAudit`) are fail-closed.
- **Non-Authoritative Domains** are fail-open only at the recorder boundary.
- **Repositories and Read Queries** must never swallow storage failures.

### What the package catches and converts

```php
// SQLSTATE 23xxx = integrity constraint violation (duplicate key)
} catch (\PDOException $e) {
    if (str_starts_with((string) $e->getCode(), '23')) {
        throw {Domain}CodeAlreadyExistsException::withCode($command->code);
    }
    throw $e; // anything else → propagate as-is
}
```

### What the package does NOT catch

- PDO infrastructure errors (connection failure, syntax error) should generally be wrapped in `SystemMaatifyException`.
- Any `\Throwable` outside the package's business domain → propagate as-is.
- Never wrap unknown errors in a named package exception if it obfuscates root causes for host.

### Transaction Pattern

In any method that uses `beginTransaction()`:

```php
$this->pdo->beginTransaction();

try {
    // ... operations
    $this->pdo->commit();
    return true;
} catch (\Throwable $e) {
    if ($this->pdo->inTransaction()) {
        $this->pdo->rollBack();
    }
    throw $e; // always rethrow — never swallow
}
```

The `throw $e` inside the catch block is **not** a violation of the "use named exceptions" rule.
It is rethrowing the original error after rollback — not creating a new exception.

---

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
- Never contains `display_order` — auto-assigned on create
- Never contains `image` — updated via dedicated method
- Host IDs (e.g. `methodId`, `currencyId`) must be validated with `>= 1` guard
- Decimal strings must be validated with `preg_match('/^\d+(?:\.\d{1,4})?$/', $value)` before any `bcmath` call
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
| `updateDisplayOrder()` | `bool` | delegated to `ScopedOrderingManager` |
| `updateImage()` | `bool` | `rowCount() > 0` |
| `softDelete()` | `bool` | `rowCount() > 0` |
| `hardDelete()` | `bool` | `rowCount() > 0` after transaction |
| `findById()` | `?DTO` | `null` if not found — Service decides whether to throw |

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

## 11. Pagination Pattern

### The Return Shape — always this exact structure

```php
/**
 * @param  array<string, string|int>  $columnFilters
 * @return array{data: list<SomeListItemDTO>, pagination: array{page: int, per_page: int, total: int, filtered: int}}
 */
public function list(
    int     $page,
    int     $perPage,
    ?string $globalSearch,
    array   $columnFilters,
    ?int    $languageId = null,
): array;
```

### The SQL Pattern — three queries, always in this order

```php
// 1. Total — unfiltered count of the entire table (no WHERE)
$stmtTotal = $this->pdo->query('SELECT COUNT(*) FROM maa_something');
if ($stmtTotal === false) {
    throw SomeDomainDatabaseException::queryFailed('Failed to count maa_something');
}
$total = (int) $stmtTotal->fetchColumn();

// 2. Filtered count — same WHERE as data query, no LIMIT
$stmtFiltered = $this->pdo->prepare(
    "SELECT COUNT(s.id) FROM maa_something s {$joinSql} {$whereSql}"
);
$stmtFiltered->execute($params);
$filtered = (int) $stmtFiltered->fetchColumn();

// 3. Data — with LIMIT + OFFSET
$offset = ($page - 1) * $perPage;
$stmt   = $this->pdo->prepare(
    "SELECT ... FROM maa_something s {$joinSql} {$whereSql} ORDER BY ... LIMIT :limit OFFSET :offset"
);
foreach ($params as $key => $value) {
    $stmt->bindValue(':' . $key, $value);
}
$stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
$stmt->execute();
```

Note: The exception thrown in the total count guard above must be a domain-specific storage exception (extending `SystemMaatifyException`), in line with the package exception policy. Creating a raw `\RuntimeException` here is forbidden.

### The WHERE Builder Pattern

```php
$where  = [];
$params = [];

if ($globalSearch !== null && trim($globalSearch) !== '') {
    $globalSearchValue = '%' . trim($globalSearch) . '%';

    $where[]              = '(s.name LIKE :global_name OR s.code LIKE :global_code)';
    $params['global_name'] = $globalSearchValue;
    $params['global_code'] = $globalSearchValue;
}

if (isset($columnFilters['id'])) {
    $where[]      = 's.id = :id';
    $params['id'] = (int) $columnFilters['id'];
}

if (isset($columnFilters['is_active'])) {
    $where[]             = 's.is_active = :is_active';
    $params['is_active'] = (int) $columnFilters['is_active'];
}

if (isset($columnFilters['deleted'])) {
    $where[] = (int) $columnFilters['deleted'] === 1
        ? 's.deleted_at IS NOT NULL'
        : 's.deleted_at IS NULL';
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
```

### The Return Array

```php
return [
    'data'       => $items,
    'pagination' => [
        'page'     => $page,
        'per_page' => $perPage,
        'total'    => $total,     // unfiltered — entire table count
        'filtered' => $filtered,  // count after WHERE applied
    ],
];
```

`total` = rows in the table regardless of any filter.
`filtered` = rows that match the current search/filters.
Frontend uses both to render pagination controls correctly.

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

However, **infrastructure packages** like `event-logging` should use their real package/domain boundaries instead.
For example, in `event-logging`, the six logging domains (e.g., `AuthoritativeAudit`, `BehaviorTrace`) serve as the public architectural boundaries rather than arbitrary Admin/Customer folders.

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

- Auto-assigned on `create` via `ScopedOrderingManager::getNextPosition()`
- Never in `CreateCommand` or `UpdateCommand`
- Updated via dedicated `updateDisplayOrder(int $id, int $displayOrder): void`
- `ScopedOrderingManager::moveWithinScope()` handles shift + clamp to valid range
- Soft delete: **no compact** — record still exists in DB
- Hard delete: `compactScopeAfterRemoval()` inside transaction — closes the gap

---

## 17. Image Rules

- `image` column is `VARCHAR(255) NULL` — stores path or URL only, never binary data
- Never in `CreateCommand` or `UpdateCommand`
- Updated via dedicated `updateImage(int $id, ?string $image): void`
- `null` clears the image (column set to NULL)
- Translation images follow the same rule via `updateTranslationImage(int $id, ?string $image): void`

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
        - tests
```

### Testing Strategy

- **Testing Requirements**: Packages that own persistence/database behavior must provide integration tests where practical. Unit/Regression suites are required where applicable.
- **Database Tests**: DB-dependent integration tests must use the real service targeted by the package-owned persistence. They must not depend on host application databases, host schemas, framework bindings, or secrets.
- **MySQL & SQLite Rules**: For MySQL-owned packages, integration tests must use a real MySQL service/DSN, not SQLite. SQLite fallback/support is forbidden for MySQL-owned packages unless SQLite is explicitly declared as a real supported persistence target for that package.
- **Example Code**: Must be purely illustrative, validated via syntax checks (`php -l`), devoid of real credentials or framework bindings.

### PDO fetch results — always annotate

```php
/** @var array<string, mixed>|false $row */
$row = $stmt->fetch(PDO::FETCH_ASSOC);

/** @var list<array<string, mixed>> $rows */
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
```

### IteratorAggregate — generic annotations required

```php
/** @implements \IteratorAggregate<int, SomethingDTO> */
final readonly class SomethingCollectionDTO implements \IteratorAggregate, \JsonSerializable

/** @return \ArrayIterator<int, SomethingDTO> */
public function getIterator(): \ArrayIterator
```

### list type annotations

```php
/** @var list<SomethingDTO> */
private array $items;

/** @param list<SomethingDTO> $items */
public function __construct(array $items)
```

### Hydration — extract before cast

```php
// ❌ PHPStan max rejects direct cast of mixed
isActive:     (bool) ($row['is_active']     ?? false),
displayOrder: (int)  ($row['display_order'] ?? 0),

// ✅ extract, check type, then cast
$isActive     = $row['is_active']     ?? null;
$displayOrder = $row['display_order'] ?? null;

isActive:     (is_int($isActive)     || is_string($isActive)) && (int) $isActive === 1,
displayOrder: (is_int($displayOrder) || is_string($displayOrder)) ? (int) $displayOrder : 0,
```

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

CI pipelines MUST be:
- **compliant with `CI_WORKFLOW_STANDARD.md`** (which serves as the single detailed source of truth)
- **reliable always-reporting required checks** (using stable gates, not fragile matrix jobs)
- **testing minimum and latest PHP compatibility**
- **testing lowest and latest dependency compatibility**
- **enforcing PHPStan max**
- **enforcing code-style dry-run** (when configured)
- **using real service Integration tests** (where applicable)
- **running Composer security audit**
- **running workflow linting**
- **following least-privilege and immutable action pinning**
- **free of hidden failures** (no silent continue-on-error)
- **free of required secrets or Host dependencies in baseline CI**

---

## 23. The Package Is NOT Done Until

- [ ] All PHPStan max errors resolved — zero errors, no suppressions
- [ ] CI workflows exist and pass
- [ ] CI workflows comply fully with `CI_WORKFLOW_STANDARD.md`
- [ ] Minimum and latest PHP compatibility tested
- [ ] Lowest and latest dependency compatibility tested
- [ ] PHPStan max passes
- [ ] Code-style dry-run passes (if configured)
- [ ] PHPUnit full suite passes (Unit, Regression, Integration where applicable)
- [ ] Example PHP files are syntax-checked where examples exist
- [ ] DB integration tests use real service dependencies where applicable
- [ ] Composer security audit passes
- [ ] Workflow files pass linting
- [ ] Required gates always report a stable status
- [ ] No baseline CI depends on host app code, host schema, framework bindings, or secrets
- [ ] `README.md` written with installation steps and quick examples
- [ ] `CHANGELOG.md` written starting at `[1.0.0]`
- [ ] `{PACKAGE}_PACKAGE_REFERENCE.md` complete — full API, design rules, extension guide
- [ ] `composer.json` metadata is correct and lists explicit runtime/Composer dependencies only (no phantom extensions)
- [ ] Every public service/repository capability intended for infrastructure substitution has a matching contract (interface)
- [ ] Domain-specific failure semantics are documented
- [ ] Transaction catch blocks rethrow the original `\Throwable` after rollback — never swallow
- [ ] Business orchestration lives in Services, validation in Commands/filters, SQL in Repositories
- [ ] Schema docs align with MySQL/domain-owned tables, no generic `logs` or `event_logs` tables
- [ ] Framework-agnostic boundaries preserved: no host app namespaces, no framework bindings required
- [ ] No generic logger, recorder, or repository
- [ ] Docs reflect current exception rules (using `SystemMaatifyException`), and the package explicitly relies on `maatify/exceptions` and `maatify/shared-common` instead of defining local duplicates
