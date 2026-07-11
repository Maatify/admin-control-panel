# Changelog

All notable changes to `maatify/persistence` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2026-07-11

### Added
* General Maatify PHP library repository presentation standard covering README structure, badges, governance-document identity, release-facing metadata, and final release-candidate review.
* Standalone PDO scoped-ordering package.
* Global and scoped ordering.
* SQL identifier validation and quoting.
* Configurable id, order, scope, and deleted-at columns.
* Optional soft-delete filtering across applicable ordering operations.
* Next-position lookup capability.
* Scoped row-existence lookup.
* Transaction-owned movement operations.
* Scope locking capability (`SELECT ... FOR UPDATE`).
* Position clamping logic.
* Package exception marker (`PersistenceException`).
* Integration with `maatify/exceptions`.
* Unit, Regression, and MySQL Integration test suites.
* Rollback failure-injection coverage.
* PHP compatibility CI.
* Lowest-supported dependency CI.
* MySQL repeatability/residue verification.
* Stable CI Gate.
* Package reference documentation (`PERSISTENCE_PACKAGE_REFERENCE.md`).
* Security policy (`SECURITY.md`).
* Affected-range-only movement without globally normalizing pre-existing gaps.

### Changed
* Refined exception architecture to distinguish between runtime validation (`InvalidOrderingOperationException`), configuration errors (`InvalidOrderingConfigurationException`), and operational constraints (`OrderingTransactionException`).
* Rolls back owned transactions after operation failures and rethrows the original throwable.
* Enforced real MySQL testing; SQLite substitution is explicitly disabled.

[Unreleased]: https://github.com/Maatify/persistence/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/Maatify/persistence/releases/tag/v1.0.0
