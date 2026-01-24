# AuthoritativeAudit Module (Compliance & Governance)

**Project:** maatify/admin-control-panel
**Module:** AuthoritativeAudit
**Namespace:** `Maatify\AuthoritativeAudit`

## Purpose
This module provides a standalone, isolated logging mechanism for **Authoritative Audit** events. It represents compliance-grade, governance-critical changes (e.g., Privileged account creation, Role assignment, System ownership changes).

**Key Characteristics:**
- **Fail-Closed:** If writing to the outbox fails, the operation MUST fail.
- **Transactional:** Writes must occur within the business transaction.
- **Outbox Pattern:** The `authoritative_audit_outbox` is the source of truth.

## Architecture

The module follows the Canonical Logger Design Standard:

1.  **Recorder** (`AuthoritativeAuditRecorder`): The policy layer. It accepts audit data, validates it (no secrets), enforces DB constraints, creates DTOs, and ensures fail-closed behavior.
2.  **Contract** (`AuthoritativeAuditOutboxWriterInterface`): The interface for the storage driver (outbox writer).
3.  **DTOs**: Strict Data Transfer Objects for Outbox Write.
4.  **Infrastructure** (`AuthoritativeAuditOutboxWriterMysqlRepository`): The MySQL implementation of the writer using PDO.
5.  **Policy** (`AuthoritativeAuditPolicyInterface`): Interface for normalizing inputs and validating payloads. A default implementation (`AuthoritativeAuditDefaultPolicy`) is provided.

### Module Boundary / Public Surface

Consumers should strictly use the defined Public API:
- **Write:** `AuthoritativeAuditRecorder::record(...)`
- **Configure:** `AuthoritativeAuditPolicyInterface`

### Data Flow

```
Caller (Business Service)
  |
  v
Start Transaction
  |
  v
Perform Business Logic (e.g. Change Role)
  |
  v
Call AuthoritativeAuditRecorder::record(...)
  |
  v
AuthoritativeAuditRecorder
  - Validates Payload (No Secrets)
  - Enforces DB Constraints
  - Normalizes Actor Type
  - Constructs DTO
  |
  v
AuthoritativeAuditOutboxWriterInterface::write(DTO)
  |
  v
AuthoritativeAuditOutboxWriterMysqlRepository (Infrastructure)
  - Serializes Payload (JSON)
  - Formats Dates (UTC)
  - Executes INSERT SQL (authoritative_audit_outbox)
  |
  v
Commit Transaction
```

## Database Schema

The module requires the `authoritative_audit_outbox` (and `authoritative_audit_log` for consumers) table. A canonical schema definition is provided within the module:

`app/Modules/AuthoritativeAudit/Database/schema.authoritative_audit.sql`

This file should be used to initialize the database table.

## Usage

```php
use Maatify\AuthoritativeAudit\Recorder\AuthoritativeAuditRecorder;
use Maatify\AuthoritativeAudit\Enum\AuthoritativeAuditRiskLevelEnum;
use Maatify\AuthoritativeAudit\Infrastructure\Mysql\AuthoritativeAuditOutboxWriterMysqlRepository;
use Maatify\AuthoritativeAudit\Services\SystemClock;

// Dependencies
$writer = new AuthoritativeAuditOutboxWriterMysqlRepository($pdo);
$clock = new SystemClock();
$recorder = new AuthoritativeAuditRecorder($writer, $clock);

// Record Event (Inside Transaction)
$pdo->beginTransaction();
try {
    // ... business logic ...

    $recorder->record(
        action: 'role.assign',
        targetType: 'user',
        targetId: 456,
        riskLevel: AuthoritativeAuditRiskLevelEnum::HIGH,
        actorType: 'ADMIN',
        actorId: 123,
        payload: ['role' => 'super-admin', 'reason' => 'Ticket #102'],
        correlationId: 'abc-123'
    );

    $pdo->commit();
} catch (\Throwable $e) {
    $pdo->rollBack();
    throw $e;
}
```

### Constraints & Guards

- **Timezone**: Dates are strictly enforced as UTC.
- **Fail-Closed**: Exceptions during logging are PROPAGATED.
- **Payload**: MUST be an array. Secrets (password, token, etc.) are forbidden and will cause validation failure.
- **String Constraints**: Strings are truncated to safe limits (action: 128, targetType: 64, correlationId: 36).
