# Catalog schema

This directory contains the Phase 1 persistence contract for the Category
taxonomy foundation.

## Included tables

- `maa_catalog_categories`
- `maa_catalog_category_translations`

Apply [catalog.sql](catalog.sql) in that order. The schema intentionally does
not include Product, Pricing, Inventory, Media, or any Host-owned table.

Timestamps are supplied by the Catalog application in UTC. Both tables use
soft deletion through nullable `deleted_at`; normal runtime hard deletion is
not part of the contract. All foreign keys are internal Catalog relationships
and use `RESTRICT` for delete and update operations.
