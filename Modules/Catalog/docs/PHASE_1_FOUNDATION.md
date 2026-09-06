# Catalog Phase 1 — Category Foundation

## Authority

The implementation follows
[`../architecture/CATALOG_V1_ARCHITECTURE.md`](../architecture/CATALOG_V1_ARCHITECTURE.md).
That architecture remains the authority for the Category schema and for all
Catalog V1 decisions.

## Implemented boundary

Phase 1 contains only framework-neutral package foundation types and the two
Category tables:

- `maa_catalog_categories`
- `maa_catalog_category_translations`

The PHP foundation exposes immutable DTOs and the status enum needed by later
phases. It does not expose repository, service, command, HTTP, or Host
integration APIs because those contracts are not established by the current
architecture.

## Validation boundary

DTOs enforce only the directly established identity invariants that can be
validated without persistence or orchestration:

- Internal IDs are positive integers.
- A Category cannot use itself as its parent.

BCP-47 language-code validation remains the Host responsibility. Category cycle
prevention, move orchestration, restore safety, delete dependencies, and all
CRUD behavior remain deferred to later phases.

## Integration verification

The package `integration` test suite runs the canonical schema against a real
MySQL-compatible engine. It covers schema installation and repeatable cleanup,
storage settings, valid category/translation inserts, foreign-key restrictions,
`CHECK` constraints, and the category and translation unique constraints.
Configure `CATALOG_TEST_DSN`, `CATALOG_TEST_DB_USER`, and
`CATALOG_TEST_DB_PASSWORD` to run it. Missing configuration fails setup instead
of silently skipping the database contract.
