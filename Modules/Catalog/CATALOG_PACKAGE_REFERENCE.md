# Catalog Package Reference

`maatify/catalog` is a standalone, host-agnostic foundation for Catalog V1
categories and category translations.

## Phase 1 scope

This phase defines only the stable Category/Taxonomy data foundation:

- Category identity, immutable code, nullable parent identity, status, display
  order, application-managed timestamps, and soft-delete representation.
- Category Translation identity `(category_id, language_code)`, `name`, and
  `description`.
- The package-local schema for `maa_catalog_categories` and
  `maa_catalog_category_translations`.

Catalog entity identity is not defined here. Product, pricing, inventory,
media, HTTP, framework integration, dependency-injection bindings, and
orchestration services are outside this phase.

## Runtime API

### `Maatify\Catalog\Category\Enum\CatalogStatusEnum`

String-backed enum values:

- `active`
- `inactive`

The enum mirrors the database status constraint. Status is independent from
soft deletion.

### `Maatify\Catalog\Category\DTO\CategoryDTO`

Immutable category record:

```php
final readonly class CategoryDTO implements \JsonSerializable
{
    public function __construct(
        public int $id,
        public ?int $parentId,
        public string $code,
        public CatalogStatusEnum $status,
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

JSON serialization uses the schema field names. Timestamps are serialized as
UTC-compatible `Y-m-d H:i:s` values, and status is serialized as its backed
string value.

### `Maatify\Catalog\Category\DTO\CategoryTranslationDTO`

Immutable category translation record:

```php
final readonly class CategoryTranslationDTO implements \JsonSerializable
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

### Exceptions and contracts

- `Maatify\Catalog\Exception\CatalogExceptionInterface` is the package marker
  contract and extends `Throwable`.
- `Maatify\Catalog\Category\Exception\CategoryInvalidArgumentException`
  represents invalid Category DTO identity input.

No repository, service, command, HTTP, or other orchestration interface is
defined in Phase 1 because the architecture does not establish those public
APIs yet.

## Persistence contract

The canonical SQL is [schema/catalog.sql](schema/catalog.sql). It contains
exactly the two Phase 1 tables and enforces:

- `BIGINT UNSIGNED` identities and internal foreign keys only.
- `ON DELETE RESTRICT` and `ON UPDATE RESTRICT` on internal relationships.
- Stable unique category codes.
- Unique `(category_id, language_code)` translation identities.
- `active|inactive` status and `parent_id <> id` checks.
- Explicit category hierarchy indexes.
- Application-managed UTC timestamps without database timestamp defaults.
- Soft-delete representation through nullable `deleted_at`.
- `InnoDB`, `utf8mb4`, and `utf8mb4_unicode_ci`.

The package does not create foreign keys or joins to Host tables.

## Deferred capabilities

The following remain separate future phases and are intentionally absent:

- Category cycle-prevention service.
- Category move orchestration.
- Child-delete dependency orchestration.
- Restore orchestration.
- CRUD services and PDO repositories.
- Slim/Admin integration.
