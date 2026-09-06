# Changelog

All notable changes to this package will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added

- Category and category-translation foundation types.
- Category and category-translation schema tables.
- Category domain/application operation DTOs, contracts, and business service
  for hierarchy, lifecycle, status, ordering, and translation invariants.
- PDO Category persistence and transaction adapters with explicit soft-delete
  read semantics, application-owned ClockInterface timestamps, row-locking
  integration tests, and shared Ordering API consumption for nullable root
  scopes with atomic display-order timestamps.
- Public Category query/list contracts with typed collection DTOs, deterministic
  `display_order, id` ordering, active/non-deleted visibility, complete
  ancestor-path filtering, and Category Translation reads without Host language
  fallback logic.
