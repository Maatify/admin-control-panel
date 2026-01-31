# Contributing Guide

> **Status:** Helper / Operational Checklist
> **Nature:** Non-binding specification. Use as a guide.

## 🟢 Pre-Work
- [ ] **Read Canonical Context**: `docs/PROJECT_CANONICAL_CONTEXT.md`
- [ ] **Check Architecture Status**: Is the component frozen? (e.g., Auth Core)
- [ ] **Verify Routes**: Check `routes/web.php` for existing naming/patterns.

## 🟡 Implementation
### Database
- [ ] **No ORM**: Use `PDO` only.
- [ ] **Strict Types**: Use `declare(strict_types=1)`.
- [ ] **Transactions**: Wrap mutations in `PDO::beginTransaction()`.

### Security
- [ ] **Auditing**: Log Authority/Security mutations to `audit_logs` (via `AuthoritativeSecurityAuditWriterInterface`).
- [ ] **Authorization**: Add `AuthorizationGuardMiddleware` to new protected routes.
- [ ] **Input Validation**: Validate `is_array($request->getParsedBody())` in Controllers.
- [ ] **DTOs**: Use strict DTOs for data transfer.

### UI/API
- [ ] **Separation**: UI Controller (HTML) vs API Controller (JSON).
- [ ] **Pagination**: Use `page`, `per_page`, `filters` pattern (if applicable).
- [ ] **Response**: Use standard `data` + `pagination` JSON envelope (for lists).

## 🔴 Documentation (Mandatory)
- [ ] **Update API Docs**: `docs/API.md` (for ANY new endpoint).
- [ ] **Update Canonical Context**: `docs/PROJECT_CANONICAL_CONTEXT.md` (if patterns change).
- [ ] **Check Schema**: Update `database/schema.sql` if DB changes.

## 🔵 Verification
- [ ] **Static Analysis**: Run `phpstan` (if available).
- [ ] **Manual Test**: Verify the flow end-to-end.
- [ ] **Audit Check**: Verify `audit_logs` and `security_events` entries created (where required).

## 🧪 Testing & Verification Compliance

> **Reference:**  
> Canonical rules are defined in  
> `docs/PROJECT_CANONICAL_CONTEXT.md` — **Section I) Testing & Verification Model (CANONICAL)**

Before considering any endpoint or feature as complete, verify the following:

- [ ] **Endpoint / Integration Tests exist** for all new or modified API endpoints
- [ ] Tests execute through the **full HTTP layer** (no direct service calls)
- [ ] Tests run against a **real test database** (never dev or production)
- [ ] Each test is **fully isolated** (transaction rollback or database reset)
- [ ] **Fail-closed behavior is verified**:
    - Unauthorized access is rejected
    - Missing permissions are rejected
    - Audit failures abort the operation
- [ ] No test relies on:
    - Mocked authorization
    - Mocked audit writers
    - Skipped middleware
- [ ] Test execution order does **not** affect results
- [ ] No residual database state remains after test execution

> ℹ️ **Reminder:**  
> This checklist is **non-authoritative**.  
> In case of conflict, **PROJECT_CANONICAL_CONTEXT.md** always wins.
