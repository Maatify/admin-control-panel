# Catalog Package Reference

`maatify/catalog` is the repository-local, host-agnostic foundation for Catalog
V1 categories and category translations. It is not currently published as an
independent Composer repository; the package name is reserved for a future
extractable distribution.

## Phase 1 and Phase 2 scope

Phase 1 defines the stable Category/Taxonomy data foundation:

- Category identity, immutable code, nullable parent identity, status, display
  order, application-managed timestamps, and soft-delete representation.
- Category Translation identity `(category_id, language_code)`, `name`, and
  `description`.
- The package-local schema for `maa_catalog_categories` and
  `maa_catalog_category_translations`.

Catalog entity identity is not defined here. Product, pricing, inventory,
media, HTTP, framework integration, and dependency-injection bindings remain
outside the package.

Phase 2 adds the framework-neutral Category domain/application contracts:

- Self-validating operation DTOs for creation, parent movement, lifecycle,
  status, display-order, and translation-content mutations.
- `CategoryCommandService` orchestration for stable-code uniqueness, parent
  existence, complete ancestor-chain cycle prevention, non-deleted-child
  deletion dependency, and mutation not-found handling.
- A transaction port owned by the application service, with PDO transaction and
  row-locking adapters for move, create, restore, and soft-delete invariants.
- PDO Category persistence adapters. They persist timestamps supplied by the
  application and delegate all display-order locking, shifting, transaction
  ownership, and target timestamp mutation to the stable
  `maatify/persistence` Ordering API. Category roots use its nullable scope
  capability (`parent_id IS NULL`).

## Runtime API

### `Maatify\Catalog\Category\Enum\CategoryStatusEnum`

String-backed enum values:

- `active`
- `inactive`

The enum mirrors the database status constraint. Status is independent from
soft deletion.

### `Maatify\Catalog\Category\DTO\CategoryDTO`

Immutable category record:

```php
final readonly class CategoryDTO
{
    public function __construct(
        public int $id,
        public ?int $parentId,
        public string $code,
        public CategoryStatusEnum $status,
        public int $displayOrder,
        public \DateTimeImmutable $createdAt,
        public \DateTimeImmutable $updatedAt,
        public ?\DateTimeImmutable $deletedAt,
    ) {}
}
```

The constructor accepts only positive IDs. `parentId` may be `null` for a root
category, but it may not equal `id`. `displayOrder` remains an integer; its
ordering scope and tie-breaker are database/domain concerns documented by the
Catalog architecture.

The DTO is a typed domain data contract. Presentation concerns are intentionally
outside this Base foundation.

### `Maatify\Catalog\Category\DTO\CategoryTranslationDTO`

Immutable category translation record:

```php
final readonly class CategoryTranslationDTO
{
    public function __construct(
        public int $id,
        public int $categoryId,
        public string $languageCode,
        public string $name,
        public ?string $description,
        public \DateTimeImmutable $createdAt,
        public \DateTimeImmutable $updatedAt,
        public ?\DateTimeImmutable $deletedAt,
    ) {}
}
```

The constructor accepts only positive `id` and `categoryId` values. Validation
of BCP-47 language codes remains the Host responsibility, as required by the
Catalog architecture.

### Category operation DTOs

The following `final readonly` DTOs validate operation inputs and normalize
canonical positive IDs:

- `CategoryIdDTO`
- `CreateCategoryDTO`
- `MoveCategoryDTO`
- `SoftDeleteCategoryDTO`
- `RestoreCategoryDTO`
- `UpdateCategoryStatusDTO`
- `UpdateCategoryDisplayOrderDTO`
- `UpdateCategoryTranslationDTO`

`UpdateCategoryTranslationDTO` accepts only translation content, so the logical
identity `(category_id, language_code)` cannot be changed through the mutation
contract. Category mutation DTOs do not expose `code`; the stable Category code
is immutable after creation.

### Exceptions and contracts

- `Maatify\Catalog\Exception\CatalogExceptionInterface` is the package marker
  contract and extends `Throwable`.
- `Maatify\Catalog\Category\Exception\CategoryInvalidArgumentException`
  represents invalid Category DTO identity input.
- `Maatify\Catalog\Category\Exception\CategoryNotFoundException`
- `Maatify\Catalog\Category\Exception\CategoryTranslationNotFoundException`
- `Maatify\Catalog\Category\Exception\CategoryCodeAlreadyExistsException`
- `Maatify\Catalog\Category\Exception\CategoryCycleException`
- `Maatify\Catalog\Category\Exception\CategoryHasNonDeletedChildrenException`
- `Maatify\Catalog\Category\Exception\CategoryTransactionException`

### Category contracts and service

- `CategoryQueryReaderInterface` reads Categories, stable codes, child
  dependency state, and translation identities. `findById()` includes
  soft-deleted rows; `findActiveById()` excludes them; the `ForUpdate` methods
  explicitly lock rows for the current application transaction.
- `CategoryCommandRepositoryInterface` defines the Category write port.
- `CategoryTranslationCommandRepositoryInterface` defines translation-content
  updates without logical-identity changes.
- `CategoryTransactionInterface` defines the transaction boundary used by the
  application service.
- `CategoryCommandServiceInterface` is the public mutation orchestration
  contract. It obtains application time from
  `Maatify\SharedCommon\Contracts\ClockInterface` and passes that timestamp to
  persistence adapters.
- `CategoryCommandService` locks the complete parent chain before a move and
  locks the category plus its non-deleted children before soft delete. The
  transaction is rolled back when a domain rule rejects the mutation.

## Persistence contract

The canonical SQL is [schema/catalog.sql](schema/catalog.sql). It contains
exactly the two Phase 1 tables and enforces:

- `BIGINT UNSIGNED` identities and internal foreign keys only.
- `ON DELETE RESTRICT` and `ON UPDATE RESTRICT` on internal relationships.
- Stable unique category codes.
- Unique `(category_id, language_code)` translation identities.
- `active|inactive` status `CHECK` and package-owned INSERT/UPDATE triggers for
  `parent_id <> id`.
- Explicit category hierarchy indexes.
- Application-managed UTC timestamps without database timestamp defaults;
  `ClockInterface` belongs to the Catalog application layer and repositories
  only persist the supplied value.
- Soft-delete representation through nullable `deleted_at`.
- `InnoDB`, `utf8mb4`, and `utf8mb4_unicode_ci`.
- Package-owned self-parent triggers are installed after the two tables because
  MySQL cannot use an `AUTO_INCREMENT` column in a `CHECK` expression.

The package does not create foreign keys or joins to Host tables.

## Integration verification

The `integration` PHPUnit suite executes `schema/catalog.sql` against a real
MySQL 8.0.16+ engine. It verifies installation of exactly two tables and both
package-owned triggers, repeatable cleanup with no trigger residue, storage
settings, valid inserts, foreign-key restrictions, `CHECK` constraints, both
unique constraints, application-owned timestamps, and transaction row-locking
behavior for hierarchy/lifecycle mutations. Configure `CATALOG_TEST_DSN`,
`CATALOG_TEST_DB_USER`, and `CATALOG_TEST_DB_PASSWORD` before running
`composer test:integration`; missing configuration is treated as a failed
test setup rather than a skipped verification.

## Deferred capabilities

The following remain separate future phases and are intentionally absent:

- Query/list contracts beyond the mutation-supporting read port.
- Slim/Admin integration.
