# Testing Strategy

## Unit Tests
*   Verify DTO immutability and instantiation.
*   Verify Enum values match canonical expectations.
*   Verify Exception inheritance.

## Integration Tests (Not included in this library)
*   Consumer application MUST verify database persistence via `PdoSecuritySignalWriter`.
*   Consumer MUST verify fail-open behavior in their Recorder implementation.

## Static Analysis
*   Must pass `phpstan analyse --level=max`.
