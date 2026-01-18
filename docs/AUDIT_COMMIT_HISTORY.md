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
- feat(crypto): add reversible crypto engine (fail-closed, library-grade)
- feat(crypto): introduce key rotation module with strict policy and tests
- feat(crypto): add HKDF module with context-based key derivation
- feat(crypto/password): introduce DI-based Argon2id + pepper password hashing module
- feat(crypto-dx): introduce DX orchestration layer with factories, facade, docs, and smoke tests
- docs(crypto): add consolidated README and HOW_TO_USE for library extraction
- feat(validation): finalize validation module with strict semantics and library-grade structure
- fix(security): replace assertion-based validation with explicit input guards in AdminController
- fix(validation): map DTO semantic validation errors to explicit HTTP 4xx responses
- fix(http): align client and resource error semantics across controllers
- feat(validation): introduce explicit ValidationGuard with fail-closed enforcement
- feat(validation): phase1 foundation (schemas, rules, guard)
- chore(ui): add protected Twig sandbox page for UI experimentation
- refactor(pagination): introduce PaginationDTO and unify list pagination across admin and session lists
- docs(canonical): lock PaginationDTO usage in canonical pagination contract
- docs(security): formalize explicit non-hierarchical permission model
- feat(email): add canonical email payload DTOs and twig templates
- feat(email): add canonical email_queue schema and document email messaging system
- feat(crypto): introduce canonical crypto facade, context registry, and lock crypto usage
- fix(auth): harden login input validation and remove legacy LoginSchema
- docs(validation): analyze schema contracts and inconsistencies
- docs(canonical): lock pagination, search, and optional date filtering contract for LIST APIs
- docs(canonical): lock pagination, filtering, and reusable LIST infrastructure
- feat(list): introduce canonical reusable LIST infrastructure
- refactor(list-query): replace legacy admin/session lists with canonical query pipeline
- fix(query): enforce canonical list validation, filtering, and session status handling
- fix(admin-query): remove invalid LIMIT backticks causing SQL syntax error
- feat(input-normalization): introduce canonical input normalization middleware
- fix(validation,list): align error and input shapes with DTO contracts
- docs(architecture): lock input normalization as canonical boundary and ADR
- docs(api): finalize and lock canonical LIST / QUERY contract
- fix(input-normalization): remove empty conditional bodies and make precedence explicit
- chore(crypto): enable key rotation via bootstrap wiring
- docs(architecture): add ADR for crypto key rotation via bootstrap wiring
- docs(api): isolate legacy endpoints and scope canonical LIST / QUERY contract
- docs(adr): align input normalization date keys with canonical LIST contract
- docs(context): clarify scope of canonical LIST / QUERY pagination contract
- fix(input-normalization): map legacy date keys into canonical nested date shape
- docs(canonical): align ListQueryDTO namespace with implemented domain structure
- docs(tests): document AS-IS state of canonical vs legacy list/query patterns
- feat(sessions): enable admin_id search (global numeric + column alias)
- feat(ui): implement modern Tailwind-based dashboard layout
- feat(sessions): support status in global search using derived logic
- feat(ui): enhance dashboard layout and add async widgets fetch logic
- docs(ui): move front-end markdown docs into docs/ui structure
- fix(schema): enforce canonical SharedListQuerySchema validation rules
- test(canonical-sessions): add full contract test suite + docs alignment
- test(admins): add canonical LIST / QUERY contract tests
- feat(notification): add unified delivery queue schema and channel enum
- feat(notification): add core DTOs for notification intent and delivery
- feat(notification): add queue writer contract for delivery enqueueing
- feat(notification): add notification-scoped crypto contracts and complete queue writer
- feat(notification): add delivery worker lifecycle and crypto contracts
- feat(notification): add sender registry for channel-based delivery resolution
- feat(notification): implement delivery worker processRow lifecycle
- feat(email): introduce smtp transport config dto and env wiring
- fix(notification): make delivery worker query compatible with sqlite tests
- feat(email): complete phase 3 rendering and smtp transport adapter
- docs(notification): add ADR for scope and admin-coupled history
- docs(adr): consolidate and formalize architectural decisions
- feat(email): complete encrypted email_queue writer with canonical payload contract
- docs(logging): lock PSR-3 usage policy and adopt maatify/psr-logger as app logger
- chore(security): remove recovery_state from repo and ignore runtime storage
- feat(email): implement email queue worker with crypto-safe decryption and smtp transport
- docs(canonical): lock Email as cross-domain infrastructure (not notification-owned)
- docs(env): add comments for PSR-3 logger configuration (path, retention, timezone)
- feat(email): activate async email pipeline with queue, crypto, worker, and renderer
- fix(email): finalize canonical DI wiring for Email worker transport
- test(email,notification): enforce notification-to-email responsibility boundaries
- schema(adr): finalize independent delivery queues and remove notification delivery
- docs(architecture): lock crypto contract and add bounded refactor plan
- docs(canonical): lock crypto context registry and enforce refactor guardrails
- docs(testing): formalize canonical testing & verification model
- chore: remove unused legacy notification delivery layer
- docs(index): add canonical documentation index for humans and AI
- docs(adr): legitimize crypto key rotation wiring and fix canonical references
- test: remove obsolete tests for deleted notification delivery code
- docs(api): sync API_PHASE1 with AS-IS routes, permissions, and legacy endpoints
- docs(api): resync API_PHASE1 with discovered AS-IS routes
- docs(audit): document unused and orphaned artifacts inventory
- docs(logging): formalize activity logs and refine logging policy boundaries
- feat(activity-log): complete activity logging write pipeline
- docs(activity-log): add README and usage guide
- feat(activity-log): add list reader, controller, integration tests, and docs
- feat(activity-log): wire UI GET route with canonical API POST query
- feat(sessions-ui): enhance sessions UI interactions and table controls
- feat(api,bootstrap): add canonical JSON validation error handling and body parsing
- chore(activity-log): introduce admin-scoped activity log facade (initial scaffold)
- fix(validation): simplify SearchQueryRule to structure-only validation
- test(validation): cover numeric and string column search cases
- feat(http): introduce RequestIdMiddleware with strict UUID v4 validation
- feat(context,auth,activity-log): introduce request/admin contexts and admin login result DTO
- fix(context): harden RequestContextResolver contracts
- feat(web-auth): log successful admin login via activity log
- refactor(ui-table): improve sessions table rendering and interactions
- feat(context): complete HTTP context injection and admin activity logging alignment
- feat(activity-logs-ui): add activity logs list view with global search and metadata modal
- feat(ui): expose Activity Logs page in sidebar
- feat(layout): add Activity Logs icon to sidebar
- feat(crypto): add canonical crypto service interfaces and DTO skeletons
- chore(agent): record as-is crypto execution baseline before service implementation
- feat(crypto): add canonical crypto service adapters and DTO fixes
- crypto: wire canonical crypto services into container (no behavior change)
- docs(agent): add cryptographic key management & rotation audit baseline
- docs(agent): add read-only key unification strategy
- docs(agent): add phased identity crypto migration strategy
- docs(agent): add full cryptographic census and usage inventory
- docs(agent): add crypto services & rotation project inventory report
- refactor(crypto): unify encrypted payload DTOs and remove unused contracts
- chore(crypto): enforce fail-closed rotation and add legacy crypto guardrails
- chore(auth): cut over to pepper ring passwords with transactional upgrade-on-login
- chore(auth): lock password governance with pepper ring and required Argon2 options
- refactor(crypto): extract env crypto key ring parsing into CryptoKeyRingConfig
- refactor(auth): extract password pepper env parsing into PasswordPepperRingConfig
- refactor(config): remove secrets from AdminConfigDTO and inject crypto/password configs directly
- refactor(crypto): remove EMAIL_ENCRYPTION_KEY, use AdminIdentifierCryptoService for admin emails
- chore(container): remove unused EMAIL_ENCRYPTION_KEY and align env bindings
- docs(agent): finalize crypto/password closure audit after controller fix
- docs(canonical): lock cryptography, password, and logging architecture post-audit
- Fix request-scoped context propagation after session authentication
- refactor(context): remove legacy HttpContextProvider wiring from container
- feat(logging): synchronize activity logging with existing audit flow
- fix(audit,security): propagate RequestContext and enforce request_id across critical flows
- chore(context): finalize AdminContext closure and fix obsolete tests
- docs(context): close context injection audit & harden canonical contracts
- db(schema): add telemetry_traces table for high-volume tracing logs
- http: enrich RequestContext with route, method, and path metadata
- security-events: introduce write-side module with contracts, DTOs, and mysql repository
- db: refactor security_events schema (actor_type, actor_id, severity, request context)
- security-events(reader): add module-level MySQL reader with pagination and strict row mapping
- security-events(module): align DTO with actor model and introduce explicit storage failure signaling
- security-events: add complete write pipeline (module, domain, http recorder)
- telemetry: introduce write-side module with domain recorder and http enrichment
- telemetry(module): add context & storage contracts and align logger abstraction
- test(security-events): add recorder + mysql repo integration coverage
- telemetry(app): add request-scoped HTTP telemetry recorder factory
- telemetry(enum): add auth, query, and exception event types
- telemetry(enum): expand event taxonomy for auth, queries, mutations, and exceptions
- telemetry(di): wire module logger, domain recorder, and http recorder factory
- telemetry(crypto): add HKDF-based email hash DTO, contract, and service
- telemetry(di): register telemetry email hasher service
- telemetry(http): add system recorder and extend factory for guest flows
- telemetry(crypto): fix phpstan by removing impossible empty-string checks in email hasher
- telemetry(auth): wire login telemetry and update container controller binding
- telemetry(stepup): record step-up verify outcome via admin recorder
- telemetry(sessions): record sessions query execution (best-effort)
- telemetry(error): record validation failures and psr-log telemetry write errors
- telemetry(http): add global HTTP request end telemetry middleware
- feat(security, telemetry): add admin self-logout telemetry and wire canonical error handlers
- feat(security, sessions): add admin-initiated single session revoke with canonical guards
- feat(security, telemetry): add admin bulk session revoke telemetry
- fix(telemetry): correct admin/system recorder invocation in HTTP middleware
- fix(telemetry): stabilize HttpRequestTelemetryMiddleware control flow
- fix(telemetry): harden legacy PDO loggers to be best-effort
- fix(telemetry): remove catch and prevent return overwrite in HttpRequestTelemetryMiddleware
- fix(telemetry): instrument TwoFactorController web flow
- fix(routes): add HttpRequestTelemetryMiddleware and remove duplicate RequestIdMiddleware
- telemetry(reader-module): add mysql trace reader contracts and dto
- chore(db): align telemetry_traces actor model with security_events
- fix(telemetry): realign telemetry_traces schema with canonical actor model
- feat(telemetry): add domain reader contracts and strict DTOs
- feat(telemetry): rebuild telemetry reader using session-style list schema
- feat(telemetry): add telemetry list query controller with authorization and activity logging
- feat(telemetry): expose has_metadata flag in telemetry list query
- feat(telemetry): add web and api routes for telemetry listing
- feat(telemetry): bind telemetry list reader interface to PDO implementation
- test(telemetry): add full telemetry coverage and fix AuthControllerTest
- feat(telemetry): expose has_metadata in list DTO and reader mapping
- test(telemetry): add coverage for PdoTelemetryListReader
- refactor(audit): disable telemetry-based audit logging and mark authoritative TODOs
- schema(audit): add actor_type to audit_outbox for authoritative audit events
- foundation(audit): establish authoritative audit write-side (frozen)
- refactor(security): route step-up failures to security_events and remove telemetry
- docs: add global logging rules and document PSR-3 diagnostic usage
- chore(auth): remove telemetry audit dependencies from authentication and authorization services
- refactor(authz): remove non-canonical audit intent from authorization decisions
- docs(adr,logging): add ADR-011 for deferred Data Access Logs and expand logging governance
- feat(activity): complete admin activity logging for operational actions
- Enforce telemetry observability-only guardrails
- fix(logging): remove authentication logging from controllers and enforce canonical auth logging
- docs(logging): add ASCII overview for logging architecture and boundaries
- Fix tests failing due to constructor signature changes
- feat(schema): add encrypted admin TOTP secrets table
- refactor(container): wire encrypted TOTP services and remove legacy bindings
- fix(security,totp): replace legacy TOTP storage with encrypted DB-backed flow
- fix(schema): normalize admin-linked ids and harden notification uniqueness
- chore(totp): remove deprecated legacy TOTP repository
- test(totp): fix broken tests to use AdminTotpSecretStoreInterface
- docs(adr): add ADR-012 for deferred unified verification codes and update AI guards
- feat(telemetry-ui): add telemetry metadata view with strict reader and UI-safe rendering
- restore(schema): reintroduce legacy tables removed in d501146a
- fix(bootstrap, schema): relax actor requirements to allow first-admin initialization

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
