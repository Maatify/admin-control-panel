# 📜 Project Commit History (Chronological)

This document provides a **chronological, append-only history**
of all commits since project inception.

It exists for:
- architectural traceability
- security audits
- onboarding context
- historical reference

This file is **read-only by convention**.
Entries MUST NOT be rewritten or reordered.

---

## 🔹 Full Commit History (Oldest → Newest)

- Initial commit
- chore(phase1): align project metadata and minimal dependencies
- feat(phase1): add admin identity core and POST /admins
- feat(phase1): add email identifier storage with blind index and encryption
- feat(phase2): blind index email lookup (read-only)
- feat(phase2): controlled email retrieval by admin identity
- chore(composer): declare openssl extension requirement
- refactor(phase2): extract database logic into repositories
- docs(roadmap): align execution roadmap and lock phases 0–2
- feat(phase3.1): introduce DTO layer for public contracts
- feat(phase3.2): introduce domain enums for state vocabulary
- feat(phase3.3): introduce domain-specific exceptions for identifier validation
- chore(phpstan): harden types for level max compliance
- docs(roadmap): close Phase 3 and align execution status
- feat(phase3): close verification flow with strict state control
- docs(phase4): close authentication phase after architecture & security review
- docs(phase5): close session security and guard phase
- chore(5.1): code cleanup, readonly consistency, and minor type fixes
- feat(phase6): implement RBAC authorization system with route-level guards
- fix(phase6): remove deprecated route argument usage
- feat(phase7): relational audit & security logging with actor/target semantics
- docs(phase7): finalize extensible audit target model before phase 8
- feat(phase8.1): notification contracts and DTOs
- feat(phase8.2): add null notification dispatcher as default no-op implementation
- feat(phase8.3): admin observability and UX hooks
- docs(phase8): lock observability and notification boundaries
- feat(phase9.1): introduce notification delivery contracts and DTOs
- feat(phase9.2): add notification channel adapters
- feat(phase9.3): notification delivery orchestration
- feat(phase9.4): notification failure handling and dead-letter persistence
- feat(phase8.4): notification read-side query model
- feat(phase10): notification channels and admin preferences
- feat(phase10.1): lock notification routing contract and architecture
- feat: Phase 10.2 & 10.3 - Channel Preference Contracts and Documentation
- Implement Phase 11.1: Admin Notification Preferences Control
- feat: add write-only admin notification persistence
- feat(Phase 11.2): Implement Admin Notification History and Read Acknowledgement
- feat(phase11.3): add admin self-audit read views with strict actor/target separation
- docs(phase12): define official Web vs API layer blueprint
- Phase 13 Revision: Layout, Generic Errors, Documentation
- feat: Enforce 2FA for Web Login (Setup & Verify)
- feat(security): add unified verification code infrastructure
- feat(web): add email verification OTP flow (phase 13.2)
- chore(db): consolidate genesis schema and refactor verification codes to identity-based model
- fix(security): harden session cookie attributes
- feat(notifications): add telegram channel linking with identity-based otp (phase 13.3)
- feat(phase13.3): identity-based otp infra and telegram channel linking
- fix(phpstan): resolve strict psr-3 logger and telegram handler typing issues
- fix(phpstan): resolve nullable enum access and strict logger typing
- fix(email): align verification with identity-based otp
- feat: implement logout flow (Phase 13.4)
- feat(phase-13.5): add secure remember-me persistent authentication
- feat(phase-13): finalize auth UX, logout, remember-me, and guest guard alignment
- feat(phase-13.7): lock auth boundaries and freeze web/api behavior
- fix(c1): enforce transactional audit, hybrid RBAC, risk-bound step-up, and recovery lock
- fix(c1): introduce authoritative audit outbox, recovery lock checks, and risk-bound step-up grants
- fix(c1.3): finalize role assignment governance and clean service injections
- fix(c1.4): correct RecoveryStateMiddleware and enforce pure fail-closed behavior
- lock(c1): close recovery governance with canonical event-based transitions
- docs: add complete onboarding guide and align env example for local setup
- chore: require ext-readline for CLI bootstrap commands
- docs(auth): lock authentication layer after final C2 review
- docs: add onboarding, security policy, and first admin setup guides
- security(c3): harden credentials storage (session tokens + passwords)
- docs(c4): add specification conformance & security validation audit report
- docs(onboarding): align guides with real bootstrap flow and runnable setup
- docs(phase1): finalize Phase 1 with API documentation and validation report
- docs(cleanup): remove obsolete FIRST_ADMIN_SETUP documentation
- feat(auth): enable Remember-Me UI and update docs
- feat(ui-auth): delegate UI auth flows to existing web controllers
- fix(ui-auth): normalize all auth redirects to UI namespace
- fix(ui-auth): ensure auth redirects land on UI GET routes
- refactor(routes): restore clean user-facing URLs for web UI
- feat(ui): register protected GET routes for admins, roles, permissions, and settings pages
- docs(admin-panel): define canonical template and enforcement rules for admin panel work
- feat(phase14): implement sessions UI skeleton and data flow (authorization pending)
- feat(auth-bootstrap): add system ownership with explicit first-admin script bootstrap
- chore(assets): normalize sessions page asset path
- security(auth): enforce session-only authentication and remove bearer support
- docs(security): add canonical authentication architecture specification
- docs(security): document canonical auth model and future session hardening options
- docs(history): add full chronological commit history and generation reference
- refactor(config): lock env handling via AdminConfig DTO and clean bootstrap artifacts
- feat(sessions): complete global sessions management with admin filtering, safe revoke, and auditing
- docs(phase): close sessions management phase with full audit verification
- docs(project): add canonical project context and execution task checklist
- docs(audit): add Phase 14 readiness optimization & refactor audit
- feat(admins): finalize Admins List (pagination, search, UI/JS parity)

---

**Status:** Append-only  
**Last Updated:** Generated from `git log --reverse`

---

## 🔧 How This History Is Generated

This commit history is generated directly from the Git repository
using the following command:

```bash
git log --reverse --pretty=format:"- %s"
````

Explanation:

* `--reverse` ensures chronological order (oldest → newest)
* `--pretty=format:"- %s"` outputs commit messages only,
  formatted as a Markdown-friendly list

This command represents the **authoritative source of truth**
for this file.

Any update to this document MUST be generated using the same command
to preserve ordering and integrity.
