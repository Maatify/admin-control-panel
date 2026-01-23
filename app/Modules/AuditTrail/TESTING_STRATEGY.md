# Testing Strategy

## Unit Tests
**Scope**: Recorder, Policy, DTO.
- Mock `AuditTrailLoggerInterface` and `ClockInterface`.
- Assert `record()` constructs correct DTO.
- Assert `record()` swallows exceptions from Logger.
- Assert Policy validates metadata size.
- Assert ActorType normalization.

## Integration Tests
**Scope**: Infrastructure (Repositories).
- Use real MySQL test database.
- `AuditTrailLoggerMysqlRepository`: Write a record, verify row exists.
- `AuditTrailQueryMysqlRepository`: Write records, query them back, verify DTO mapping.
- Verify `DateTimeImmutable` timezone handling (UTC).
- Verify JSON metadata serialization/deserialization.
- Verify Cursor Pagination logic.

## Static Analysis
- Must pass `phpstan analyse --level=max`.
