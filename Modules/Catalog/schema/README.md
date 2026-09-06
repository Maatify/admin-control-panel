# Catalog schema

This directory contains the Phase 1 persistence contract for the Category
taxonomy foundation.

## Included tables

- `maa_catalog_categories`
- `maa_catalog_category_translations`

Apply [catalog.sql](catalog.sql) in that order. It creates exactly the two
tables above and the package-owned Category self-parent triggers
`trg_maa_catalog_categories_parent_not_self_ai` and
`trg_maa_catalog_categories_parent_not_self_bu`. The schema intentionally does
not include Product, Pricing, Inventory, Media, or any Host-owned table.

Timestamps are supplied by the Catalog application in UTC. Both tables use
soft deletion through nullable `deleted_at`; normal runtime hard deletion is
not part of the contract. All foreign keys are internal Catalog relationships
and use `RESTRICT` for delete and update operations.

The Category invariant `parent_id IS NULL OR parent_id <> id` is enforced by
the two triggers because MySQL does not allow a `CHECK` constraint to reference
an `AUTO_INCREMENT` column. The status invariant remains a database `CHECK`.
