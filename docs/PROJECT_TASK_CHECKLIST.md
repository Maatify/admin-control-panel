# Project Task Checklist

> **Status:** Helper / Operational Checklist
> **Nature:** Non-binding specification. Use as a guide.

## 🟢 Pre-Work
- [ ] **Read Canonical Context**: `docs/PROJECT_CANONICAL_CONTEXT.md`
- [ ] **Check Phase Status**: Is the component frozen? (e.g., Auth Phase 1-13)
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
- [ ] **Update API Docs**: `docs/API_PHASE1.md` (for ANY new endpoint).
- [ ] **Update Canonical Context**: `docs/PROJECT_CANONICAL_CONTEXT.md` (if patterns change).
- [ ] **Check Schema**: Update `database/schema.sql` if DB changes.

## 🔵 Verification
- [ ] **Static Analysis**: Run `phpstan` (if available).
- [ ] **Manual Test**: Verify the flow end-to-end.
- [ ] **Audit Check**: Verify `audit_logs` and `security_events` entries created (where required).
