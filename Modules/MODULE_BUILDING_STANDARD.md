# MODULE_BUILDING_STANDARD

**Maatify Module Building Standard — v1**
This document is the law for building any new standalone module in the Maatify ecosystem.
Read it fully before writing a single line of code.

---

## 1. The Module Contract

Every module must be:

- **Standalone** — runs independently with no knowledge of the host project internals
- **Extractable** — can be packaged as a `composer require` library
- **Host-agnostic** — never FKs or JOINs on host tables. Host provides IDs; module trusts them
- **PDO-based** — all persistence uses PDO directly. No ORM, no external query builder.
  Small internal SQL fragment builders are allowed only for repeated module-local query logic.
- **PHPStan max** — zero errors at level max before the module is considered done

---

## 2. Required Files

Every module must contain these files at its root:

```
Modules/{ModuleName}/
├── README.md                          ← installation, quick examples, what it does / does not
├── CHANGELOG.md                       ← versioned history, starting at [1.0.0]
├── {MODULE_NAME}_MODULE_REFERENCE.md  ← complete API reference and design rules
├── composer.json                      ← library type, psr-4 autoload, requires
├── phpstan.neon                       ← level: max, paths: [src]
├── schema/                            ← one or more .sql files
└── src/                               ← all PHP source code
```

---

## 3. Namespace

Pattern: `Maatify\{ModuleName}\`

```
Maatify\Cart\
Maatify\Shipping\
Maatify\PaymentMethod\
Maatify\{NextModule}\
```

---

## 4. Directory Structure Inside `src/`

```
src/
├── Bootstrap/
│   └── {ModuleName}Bindings.php
│
├── Exception/
│   ├── {ModuleName}ExceptionInterface.php
│   ├── {ModuleName}NotFoundException.php
│   ├── {ModuleName}InvalidArgumentException.php
│   ├── {ModuleName}CodeAlreadyExistsException.php
│   ├── {ModuleName}ConflictException.php
│   └── ...                                          ← add only when genuinely distinct
│
├── Shared/                                          ← cross-cutting within the module
│   ├── Contract/
│   ├── DTO/
│   ├── Infrastructure/
│   │   ├── Persistence/Support/
│   │   │   └── ScopedOrderingManager.php            ← if display_order is needed
│   │   └── Support/                                 ← internal SQL fragment builders, helpers
│   └── Service/
│
├── Admin/
│   └── {Entity}/
│       ├── Command/
│       ├── Contract/
│       ├── DTO/
│       ├── Infrastructure/Repository/
│       └── Service/
│
└── Customer/
    └── {Entity}/
        ├── Contract/
        ├── DTO/
        ├── Infrastructure/Repository/
        └── Service/
```

---

## 5. Schema Rules

- Table prefix: `maa_{module_short_name}_`
- Every table needs: `PRIMARY KEY (id)`, proper indexes, meaningful COMMENTs on columns
- All policies (soft delete, display order, FK behavior, uniqueness) documented in the SQL header
- No FK constraints on host tables — use `COMMENT 'Host-provided ID. No FK.'`
- Soft delete: `deleted_at DATETIME NULL` — `NULL = active`, `NOT NULL = soft-deleted`
- Hard delete: always in a transaction with any required cleanup (e.g. compact display_order)
- Split schema into logical files if restrictions or addons exist separately

---

## 6. Exception Rules

### Standard Exception Types

Every module must define these exception classes as a minimum:

| Class | When to throw |
|---|---|
| `{Module}ExceptionInterface` | Interface — all module exceptions implement this |
| `{Module}NotFoundException` | Record not found by id or code |
| `{Module}InvalidArgumentException` | Invalid value in any command, filter, or input |
| `{Module}CodeAlreadyExistsException` | Unique code/key violation on create or update |
| `{Module}ConflictException` | Business rule conflict (e.g. overlapping records) |

Add more only when a genuinely distinct error category exists.

### Interface

```php
interface {ModuleName}ExceptionInterface extends \Throwable {}
```

### All exceptions extend `\RuntimeException` and implement the interface

```php
final class {ModuleName}NotFoundException extends \RuntimeException
    implements {ModuleName}ExceptionInterface
{
    public static function withId(int $id): self
    {
        return new self("Record with id [{$id}] not found.");
    }

    public static function withCode(string $code): self
    {
        return new self("Record with code [{$code}] not found.");
    }
}

final class {ModuleName}InvalidArgumentException extends \RuntimeException
    implements {ModuleName}ExceptionInterface
{
    public static function emptyField(string $field): self
    {
        return new self("Field [{$field}] must not be empty.");
    }

    public static function invalidId(string $field): self
    {
        return new self("Field [{$field}] must be a positive integer >= 1.");
    }

    public static function invalidDecimal(string $field, string $given): self
    {
        return new self("Invalid decimal for [{$field}]: [{$given}].");
    }
}
```

Named constructors are **required** for every exception — never `new SomeException('...')` at call site.

### What the module catches and converts

```php
// SQLSTATE 23xxx = integrity constraint violation (duplicate key)
} catch (\PDOException $e) {
    if (str_starts_with((string) $e->getCode(), '23')) {
        throw {ModuleName}CodeAlreadyExistsException::withCode($command->code);
    }
    throw $e; // anything else → propagate as-is
}
```

### What the module does NOT catch

- PDO infrastructure errors (connection failure, syntax error) → propagate as `\PDOException`
- Any `\Throwable` outside the module's business domain → propagate as-is
- Never wrap unknown errors in a named module exception — host must see the real error

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

## 7. Command Rules

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

## 8. DTO Rules

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

## 9. Repository Rules

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

## 10. Pagination Pattern

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
    throw new \RuntimeException('Failed to count maa_something');
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

Note: `new \RuntimeException(...)` in the total count guard above is an **infrastructure guard**,
not a domain/business error. It is acceptable here because there is no meaningful recovery.

### The WHERE Builder Pattern

```php
$where  = [];
$params = [];

if ($globalSearch !== null && trim($globalSearch) !== '') {
    $where[]          = '(s.name LIKE :global OR s.code LIKE :global)';
    $params['global'] = '%' . trim($globalSearch) . '%';
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

## 11. Translation Pattern

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

## 12. Service Rules

Responsibility:
- **Business orchestration** lives in Services
- **Validation** lives in Commands and DTO filters
- **SQL** lives in Repositories or module-local SQL support builders

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

## 13. Admin vs Customer Separation

| Layer | Namespace | Access |
|---|---|---|
| Admin | `Maatify\{Module}\Admin\{Entity}\` | Full CRUD, all fields, inactive + deleted records |
| Customer | `Maatify\{Module}\Customer\{Entity}\` | Active only, translated fields, minimal DTO |

- Admin DTOs expose: `notes`, `provider`, `deleted_at`, all internal fields
- Customer DTOs expose: only what the end user needs to see
- Never mix admin and customer query logic in the same repository

---

## 14. display_order Rules

- Auto-assigned on `create` via `ScopedOrderingManager::getNextPosition()`
- Never in `CreateCommand` or `UpdateCommand`
- Updated via dedicated `updateDisplayOrder(int $id, int $displayOrder): void`
- `ScopedOrderingManager::moveWithinScope()` handles shift + clamp to valid range
- Soft delete: **no compact** — record still exists in DB
- Hard delete: `compactScopeAfterRemoval()` inside transaction — closes the gap

---

## 15. Image Rules

- `image` column is `VARCHAR(255) NULL` — stores path or URL only, never binary data
- Never in `CreateCommand` or `UpdateCommand`
- Updated via dedicated `updateImage(int $id, ?string $image): void`
- `null` clears the image (column set to NULL)
- Translation images follow the same rule via `updateTranslationImage(int $id, ?string $image): void`

---

## 16. Bootstrap / DI Rules

```php
final class {ModuleName}Bindings
{
    /** @param ContainerBuilder<Container> $builder */
    public static function register(ContainerBuilder $builder): void
    {
        $builder->addDefinitions([

            // ── Shared ────────────────────────────────────────────────────

            SomeSharedClass::class => static function (ContainerInterface $c): SomeSharedClass {
                /** @var PDO $pdo */
                $pdo = $c->get(PDO::class);
                return new SomeSharedClass($pdo);
            },

            // ── Admin — {Entity} ──────────────────────────────────────────

            SomeCommandRepositoryInterface::class => static function (ContainerInterface $c): PdoSomeCommandRepository {
                /** @var PDO $pdo */
                $pdo = $c->get(PDO::class);
                return new PdoSomeCommandRepository($pdo);
            },

            SomeCommandService::class => static function (ContainerInterface $c): SomeCommandService {
                /** @var SomeCommandRepositoryInterface $commandRepo */
                $commandRepo = $c->get(SomeCommandRepositoryInterface::class);
                return new SomeCommandService($commandRepo);
            },

        ]);
    }
}
```

Rules:
- PSR-11 compatible
- Uses `php-di/php-di` `ContainerBuilder` (suggested, not required)
- Every `$c->get()` call preceded by an explicit `/** @var Type */` annotation
- Sections separated by comments: `// ── Admin — {Entity} ──`
- No business logic in bindings — wiring only

---

## 17. Decimal / Financial Rules

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

## 18. PDO Named Placeholder Rule

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

## 19. PHPStan Rules

### `phpstan.neon`

```neon
parameters:
    level: max
    paths:
        - src
```

### PDO fetch results — always annotate

```php
/** @var array<string, mixed>|false $row */
$row = $stmt->fetch(PDO::FETCH_ASSOC);

/** @var list<array<string, mixed>> $rows */
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
```

### Admin list — full return shape annotation

```php
/**
 * @param  array<string, string|int>  $columnFilters
 * @return array{data: list<SomeListItemDTO>, pagination: array{page: int, per_page: int, total: int, filtered: int}}
 */
public function list(int $page, int $perPage, ?string $globalSearch, array $columnFilters): array;
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

### DI container bindings

```php
/** @param ContainerBuilder<Container> $builder */
public static function register(ContainerBuilder $builder): void

/** @var PDO $pdo */
$pdo = $c->get(PDO::class);

/** @var SomeInterface $dep */
$dep = $c->get(SomeInterface::class);
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

## 20. The Module Is NOT Done Until

- [ ] All PHPStan max errors resolved — zero errors, no suppressions
- [ ] All schema files complete with header comments and column policies
- [ ] `README.md` written with installation steps and quick examples
- [ ] `CHANGELOG.md` written starting at `[1.0.0]`
- [ ] `{MODULE}_MODULE_REFERENCE.md` complete — full API, design rules, extension guide
- [ ] `composer.json` lists only actual dependencies (no phantom extensions)
- [ ] `Bootstrap/{ModuleName}Bindings.php` covers every public service and interface
- [ ] Every public service/repository capability intended for infrastructure substitution has a matching contract (interface)
- [ ] No `new \RuntimeException(...)` or `new \Exception(...)` for domain/business errors — always a named module exception
- [ ] Transaction catch blocks rethrow the original `\Throwable` after rollback — never swallow
- [ ] Business orchestration lives in Services, validation in Commands/filters, SQL in Repositories
- [ ] No SQL outside Repository classes and module-local SQL support builders
