# Testing Strategy

## Unit Tests
**Scope**: Recorder, Policy, DTO.
- Mock `SecuritySignalsLoggerInterface` and `ClockInterface`.
- Assert `record()` constructs correct DTO.
- Assert `record()` swallows exceptions from Logger.
- Assert Policy validates metadata size.
- Assert ActorType normalization.

## Integration Tests
**Scope**: Infrastructure (Repositories).
- Use real MySQL/SQLite test database.
- `SecuritySignalsLoggerMysqlRepository`: Write a record, verify row exists.
- Verify `DateTimeImmutable` timezone handling (UTC).
- Verify JSON metadata serialization/deserialization.

## Static Analysis
- Must pass `phpstan analyse --level=max`.
