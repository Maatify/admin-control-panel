# Changelog

All notable changes to the `Maatify\SharedCommon` module will be documented in this file.

## [Unreleased]

### Added
- **Bootstrap Layer:** Introduced `SharedCommonBindings` class in the `Maatify\SharedCommon\Bootstrap` namespace to provide a simple, repeatable way to register `ClockInterface` to the `SystemClock` implementation in PHP-DI containers.
- **Standalone Package Preparation:** Introduced a `composer.json` to define `maatify/shared-common` as an independent library, specifying PHP 8.2 requirements and PSR-4 autoloading rules.
- **Comprehensive Documentation:** Added full documentation including `README.md`, `HOW_TO_USE.md`, this `CHANGELOG.md`, and an architectural Book detailing the purpose, domain objects, clock abstraction, integration patterns, and extension points.

### Changed
- Refactored the module to support being extracted into an independent reusable library. This change involves establishing standard packaging metadata and clear documentation on how other modules depend on it, without altering any existing application behavior.
