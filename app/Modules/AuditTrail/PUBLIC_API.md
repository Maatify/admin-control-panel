# Public API Definition

## Recorder
`Maatify\AuditTrail\Recorder\AuditTrailRecorder`

- `record(...)`: Primary entry point. Returns `void`. Never throws.

## Contracts
`Maatify\AuditTrail\Contract\AuditTrailLoggerInterface`
- `write(AuditTrailRecordDTO $record): void`

`Maatify\AuditTrail\Contract\AuditTrailQueryInterface`
- `find(AuditTrailQueryDTO $query): array<AuditTrailViewDTO>`

`Maatify\AuditTrail\Contract\AuditTrailPolicyInterface`
- `normalizeActorType(...)`
- `validateMetadataSize(...)`

## DTOs
`Maatify\AuditTrail\DTO\AuditTrailRecordDTO` (Immutable, Read-only)
`Maatify\AuditTrail\DTO\AuditTrailViewDTO` (Immutable, Read-only)
`Maatify\AuditTrail\DTO\AuditTrailQueryDTO` (Read-only properties)

## Enum
`Maatify\AuditTrail\Enum\AuditTrailActorTypeEnum`
- SYSTEM, ADMIN, USER, SERVICE, API_CLIENT, ANONYMOUS
