<div align="center">

# Catalog

![Maatify.dev](https://www.maatify.dev/assets/img/img/maatify_logo_white.svg)

Standalone category taxonomy foundation for Maatify Catalog V1.

</div>

---

## Package Summary

`maatify/catalog` provides the framework-neutral Category and Category
Translation foundation for a future extractable Catalog package.

## Key Features

- Immutable, typed Category and Category Translation DTOs.
- `active` / `inactive` status enum.
- Nullable parent identity for category hierarchy.
- Application-managed timestamp and soft-delete representation.
- MySQL-compatible Category schema with internal restricted foreign keys.

Product, pricing, inventory, media, Admin/Slim integration, repositories, and
orchestration services are not part of this phase.

## Requirements

- PHP 8.2 or later.
- Composer.

## Installation

```bash
composer require maatify/catalog
```

## Quick Usage

```php
use Maatify\Catalog\Category\DTO\CategoryDTO;
use Maatify\Catalog\Category\Enum\CatalogStatusEnum;

$category = new CategoryDTO(
    id: 1,
    parentId: null,
    code: 'clothing',
    status: CatalogStatusEnum::ACTIVE,
    displayOrder: 0,
    createdAt: new DateTimeImmutable('2026-01-01 00:00:00 UTC'),
    updatedAt: new DateTimeImmutable('2026-01-01 00:00:00 UTC'),
    deletedAt: null,
);
```

## Documentation

- [Catalog Package Reference](CATALOG_PACKAGE_REFERENCE.md)
- [Category schema](schema/catalog.sql)
- [Schema notes](schema/README.md)
- [Phase 1 foundation notes](docs/PHASE_1_FOUNDATION.md)
- [Changelog](CHANGELOG.md)

## Quality Status

The Phase 1 package checks are defined by `composer.json`, `phpstan.neon`, and
`phpunit.xml.dist`. Run them from `Modules/Catalog/`:

```bash
composer validate --strict
composer dump-autoload --optimize --strict-psr
composer analyse
composer test
```

## License

MIT. See [LICENSE](LICENSE).

## 👤 Author

Engineered by **Mohamed Abdulalim** ([@megyptm](https://github.com/megyptm))<br>
Backend Lead & Technical Architect<br>
[https://www.maatify.dev](https://www.maatify.dev)

---

<div align="center">

[Built with ❤️ by Maatify.dev — Unified Ecosystem for Modern PHP Libraries](https://www.maatify.dev)

</div>
