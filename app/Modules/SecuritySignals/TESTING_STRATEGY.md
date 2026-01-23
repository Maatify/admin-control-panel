# Testing Strategy

**NOTE: No tests are required for the SecuritySignals module in this project phase.**
**Scope is strictly implementation only.**

## Future Scope (Reference Only)

### Unit Tests
- Mock `SecuritySignalsLoggerInterface` and `ClockInterface`.
- Assert `record()` constructs correct DTO.
- Assert `record()` swallows exceptions from Logger.
- Assert Policy validates metadata size.
- Assert ActorType normalization.

### Integration Tests
- Use real MySQL test database.
- `SecuritySignalsLoggerMysqlRepository`: Write a record, verify row exists.
- Verify `DateTimeImmutable` timezone handling (UTC).
- Verify JSON metadata serialization/deserialization.

### Static Analysis
- Must pass `phpstan analyse --level=max`.
