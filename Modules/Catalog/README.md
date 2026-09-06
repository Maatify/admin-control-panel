<div align="center">

# Catalog

![Maatify.dev](https://www.maatify.dev/assets/img/img/maatify_logo_white.svg)

In-repository category taxonomy foundation for Maatify Catalog V1.

</div>

---

## Package Summary

The Catalog module provides the framework-neutral Category and Category
Translation foundation for a future extractable package. It currently lives
inside the `maatify/admin-control-panel` repository and is not published as a
standalone Composer repository.

## Key Features

- Immutable, typed Category and Category Translation DTOs.
- `active` / `inactive` status enum.
- Nullable parent identity for category hierarchy.
- Application-managed timestamp and soft-delete representation.
- MySQL Category schema with internal restricted foreign keys and package-owned
  self-parent triggers.

Product, pricing, inventory, media, Admin/Slim integration, repositories, and
orchestration services are not part of this phase.

## Requirements

- PHP 8.2 or later.
- Composer.
- MySQL 8.0.16 or later for Integration verification.

## Distribution status

This phase is repository-local. Do not use a standalone `composer require`
command for it until an independently published package repository exists.

## Quick Usage

```php
use Maatify\Catalog\Category\DTO\CategoryDTO;
use Maatify\Catalog\Category\Enum\CategoryStatusEnum;

$category = new CategoryDTO(
    id: 1,
    parentId: null,
    code: 'clothing',
    status: CategoryStatusEnum::ACTIVE,
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
composer test:unit
CATALOG_TEST_DSN='mysql:host=127.0.0.1;port=3306;dbname=catalog_test;charset=utf8mb4' \
CATALOG_TEST_DB_USER='catalog_test' CATALOG_TEST_DB_PASSWORD='secret' \
composer test:integration
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
