# Public API Definition

## Recorder
`Maatify\SecuritySignals\Recorder\SecuritySignalsRecorder`

- `record(...)`: Primary entry point. Returns `void`. Never throws.

## Contracts
`Maatify\SecuritySignals\Contract\SecuritySignalsLoggerInterface`
- `write(SecuritySignalRecordDTO $record): void`

`Maatify\SecuritySignals\Contract\SecuritySignalsPolicyInterface`
- `normalizeActorType(...)`
- `normalizeSeverity(...)`
- `validateMetadataSize(...)`

## DTOs
`Maatify\SecuritySignals\DTO\SecuritySignalRecordDTO` (Immutable, Read-only)

## Enum
`Maatify\SecuritySignals\Enum\SecuritySignalActorTypeEnum`
- SYSTEM, ADMIN, USER, SERVICE, API_CLIENT, ANONYMOUS

`Maatify\SecuritySignals\Enum\SecuritySignalSeverityEnum`
- INFO, WARNING, ERROR, CRITICAL
