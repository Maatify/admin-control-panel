# Versioning Policy

`maatify/exceptions` adheres to [Semantic Versioning 2.0.0](https://semver.org/).

## Version Format

`MAJOR.MINOR.PATCH`

### MAJOR (Breaking Changes)

*   Removing an exception class.
*   Changing the signature of `ApiAwareExceptionInterface`.
*   Renaming or removing an `ErrorCategoryEnum` value.
*   Removing an `ErrorCodeEnum` value.
*   Changing the default HTTP status code of an exception family (e.g., Validation 400 -> 500).

### MINOR (New Features)

*   Adding a new exception class (backward compatible).
*   Adding a new `ErrorCodeEnum` value.
*   Adding a new `ErrorCategoryEnum` value (rare).
*   Adding a new method to `MaatifyException` (protected or private).

### PATCH (Bug Fixes)

*   Fixing internal logic in `MaatifyException` (e.g., escalation calculation).
*   Correcting typos in exception messages.
*   Internal refactoring that does not affect the public API.

## Stability Guarantees

*   **v1.x:** We commit to maintaining backward compatibility for the entire v1.x lifecycle.
*   **Deprecations:** Any feature scheduled for removal in v2.0 will be marked as `@deprecated` in a v1.x minor release at least 6 months prior.
