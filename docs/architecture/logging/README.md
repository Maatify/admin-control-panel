# Logging Integration — Index

> **Notice:** The canonical architecture for logging is now owned by `maatify/event-logging`.
> `admin-control-panel` is a host application that consumes this package.
> This directory only contains host-integration notes, migration history, and wiring context.
>
> **Do NOT duplicate the canonical package architecture here.**
> For all domain rules, blueprints, and specifications, refer to the `maatify/event-logging` repository.

## Table of Contents

- [Event Logging Migration Blueprint](./EVENT_LOGGING_MIGRATION_BLUEPRINT.md)
  - Details how the host application (`admin-control-panel`) migrated to the `maatify/event-logging` library, including namespace mapping and repository adapter patterns.

## Deferred Features (Implementation Notes)
The following features are deferred and not currently supported:
* Archive tables
* MySQL-to-MySQL archiving
* Checkpointing
* Retention policies
* Hot + archive reads
* AuthoritativeAudit consumer/materializer
* Dead-letter/manual intervention semantics

## Unsupported Features
The following features are strictly unsupported in the package and host:
* MongoDB archiving
* Dual-write storage strategies
* Generic cross-domain logger
* Generic log table
* Advanced UI-grid querying inside the package
* Host-specific routes/controllers/middleware/permissions inside the package
