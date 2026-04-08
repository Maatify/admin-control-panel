You are in FULL EXECUTION SYSTEM MODE.

You are NOT allowed to operate outside this system.

---

## SOURCE OF TRUTH (MANDATORY)

All documents live under `docs/architecture/execution/`. You MUST read the relevant files before generating any code.

### Backend Rules
1. `docs/architecture/execution/backend/HTTP_EXECUTION_RULES.md`
2. `docs/architecture/execution/backend/EXECUTION_MIGRATION_STRATEGY.md`
3. `docs/architecture/execution/backend/FEATURE_EXECUTION_REALITY.md`
4. `docs/architecture/execution/backend/EXECUTION_ENVIRONMENT_RULES.md`

### Frontend Rules
5. `docs/architecture/execution/frontend/UI_EXECUTION_RULES.md`
6. `docs/architecture/execution/frontend/UI_INTERACTION_DEPTH_ANALYSIS.md`
7. `docs/architecture/execution/frontend/TWIG_TEMPLATE_STANDARDS.md`
8. `docs/architecture/execution/frontend/JS_PATTERNS_REFERENCE.md`
9. `docs/architecture/execution/frontend/DEVELOPMENT_STANDARDS.md`
10. `docs/architecture/execution/frontend/IMPLEMENTATION_GUIDE.md`
11. `docs/architecture/execution/frontend/IMPLEMENTATION_CHECKLIST.md`

### Component Documentation
12. `docs/architecture/execution/components/DATA_TABLE_DOCUMENTATION.md`
13. `docs/architecture/execution/components/API_HANDLER_DOCUMENTATION.md`
14. `docs/architecture/execution/components/REUSABLE_COMPONENTS_GUIDE.md`
15. `docs/architecture/execution/components/SELECT2_DOCUMENTATION.md`
16. `docs/architecture/execution/components/WYSIWYG_MANAGER_DOCUMENTATION.md`
17. `docs/architecture/execution/components/COMMON_MISTAKES.md`
18. `docs/architecture/execution/components/500_ERROR_DEBUGGING.md`
19. `docs/architecture/execution/components/CONSOLE_LOGGING_GUIDE.md`

---

## FEATURE API CONTRACTS (MANDATORY)

Feature contracts live under `docs/API/`.

Each file defines the **binding runtime contract** for one feature:
- Exact endpoint URLs
- Exact request payload shape and field constraints
- Exact response shapes (success + error)
- Validation rules enforced by the backend
- Capability names required
- What the UI is explicitly forbidden to do

**Before implementing any feature:**
1. Check if `docs/API/{feature}.md` exists.
2. If found → read it **completely and first** before reading anything else.
3. It is BINDING. If something is not described in the contract → treat it as **not supported**.

---

## API CONTRACT MAINTENANCE (BACKEND — MANDATORY)

After implementing or modifying any backend endpoint, the executor MUST update `docs/API/`.

This is NOT optional. No backend task is complete until the contract is written or updated.

### Rule 1 — New feature or new module

If the implemented endpoint belongs to a feature that has no existing contract file:

→ Create `docs/API/{feature}.md`

The file MUST include:
- Module name and purpose
- All endpoints implemented in this task (URL, method, route name)
- Request payload: every field, type, required/optional, validation rule
- Response shape: success envelope and all error shapes
- Capability names required for each endpoint
- What the UI must NOT do (forbidden behaviors)

### Rule 2 — Existing module, new endpoint

If the implemented endpoint belongs to a module that already has a contract file:

→ Open `docs/API/{feature}.md` and add the new endpoint section.

Do NOT create a new file. Add to the existing one.
Maintain the same structure as existing sections in that file.

### Rule 3 — Modified endpoint

If an existing endpoint's behavior changed (payload, validation, response shape, capability):

→ Open `docs/API/{feature}.md` and update the relevant section.

Mark changed fields explicitly. Do not silently overwrite — if a rule changed, say what it was before and what it is now, so the frontend knows to update.

### Rule 4 — Contract file structure

Every `docs/API/{feature}.md` MUST follow this structure:

```markdown
# {Feature Name} — UI & API Integration Guide

**Module:** {module path}
**Status:** CANONICAL / BINDING CONTRACT

## 0) Why this document exists
{one paragraph — what this contract covers}

## 1) Endpoints

### {Endpoint Name}
**Method:** POST / GET
**URL:** /api/{path}
**Route name:** {route.name.api}
**Capability:** {can_something}

#### Request Payload
| Field | Type | Required | Validation |
|-------|------|----------|------------|

#### Response — Success
{json example}

#### Response — Error
{json example with status code}

## 2) What the UI must NOT do
- {forbidden behavior}
- {forbidden behavior}

## 3) Implementation Checklist
- [ ] {check}
```

### Rule 5 — Timing

The contract file MUST be written **in the same task** as the backend implementation.

Not in a follow-up task. Not after frontend review. In the same delivery.

If the task is backend-only, the contract file is the handoff artifact to the frontend.

---

## CONFLICT RESOLUTION (AUTHORITATIVE ORDER)

### What each system defines

```
docs/API/{feature}.md        → WHAT: the behavioral contract for this specific feature
docs/architecture/execution/ → HOW: the system rules for implementation
```

### When they conflict

**API Contract defines WHAT to do. Execution rules define HOW to do it.**

In the rare case of a genuine conflict:

1. **API Contract wins** for behavioral decisions:
   - Which endpoint to call
   - What fields to send
   - What response shape to expect
   - What capability names to use

2. **Execution rules always apply** for implementation decisions:
   - Which JS pattern to use
   - How to call ApiHandler.call()
   - Where capabilities go in Twig
   - How pagination works

3. **REALITY overrides architectural rules** — but NOT API contracts.
   - REALITY means: existing production behavior confirmed in the codebase.
   - REALITY overrides: theoretical rules in execution docs that contradict how the system actually works.
   - REALITY does NOT override: an API contract, unless the production behavior actively contradicts the contract AND the contradiction is provably intentional.
   - If REALITY and API contract conflict → flag it explicitly. Do NOT silently pick one. Report: "CONTRACT vs REALITY CONFLICT DETECTED."

### HOW TO DETECT A CONFLICT (THE CONFLICT MATRIX)

To reliably detect if a "REALITY vs RULE" mismatch is a true conflict or an acceptable variation, the agent MUST execute the following matrix logic against its selected Source of Truth reference file:

1. **Rule = MUST / NEVER** + **Reference = DOES NOT** → **CONFLICT (STOP)**
2. **Rule = API CONTRACT** + **Reference = DIFFERENT** → **CONFLICT (STOP)**
3. **Rule = GENERAL PATTERN** + **Reference = SPECIFIC VARIATION** → **REALITY OVERRIDE (LOG IT, PROCEED)**

*Mandatory:* Any time a REALITY OVERRIDE is applied, the agent MUST explicitly log it in its output: `OVERRIDE APPLIED: [Rule Name] bypassed in favor of [Reference File]`. Silent overrides are strictly forbidden.

### No contract found

If `docs/API/{feature}.md` does not exist:

- Use PHASE 1 classification to determine the path:
  - **NEW feature** → implement using execution rules only. A contract MUST be created as part of this task (see API CONTRACT MAINTENANCE).
  - **LEGACY feature** → follow `EXECUTION_MIGRATION_STRATEGY.md` and `FEATURE_EXECUTION_REALITY.md`.
  - **MODIFIED feature** → follow `EXECUTION_MIGRATION_STRATEGY.md`. Update or create the contract.

---

## GLOBAL BEHAVIOR

- You MUST prioritize REALITY over theoretical architecture.
- REALITY means: verified existing production behavior — not assumption.
- You MUST NOT blindly enforce rules if they break existing behavior.
- You MUST distinguish between NEW, LEGACY, and MODIFIED code.
- If a pattern is consistently used across the codebase, treat it as VALID even if it conflicts with a theoretical rule.
- REALITY does NOT override API contracts. See CONFLICT RESOLUTION above.

---

## EXECUTION PIPELINE (MANDATORY)

### PHASE 1 — CLASSIFICATION
Classify the task internally as: NEW / LEGACY / MODIFIED.
Do NOT include this classification in final output.

---

### PHASE 1.5 — DESIGN ALIGNMENT (UI/FRONTEND ONLY)

### UI FRONTEND PRE-READ GATE (MANDATORY)

Before any UI implementation choice, the executor MUST complete this runtime pre-read sequence:

1. `public/assets/maatify/admin-kernel/js/admin-page-bridge.js`
2. `public/assets/maatify/admin-kernel/js/ADMIN_PAGE_BRIDGE_USAGE.md`
3. target `*-v2.js` files under `public/assets/maatify/admin-kernel/js/pages/**`
4. mounted Twig page(s) under `app/Modules/AdminKernel/Templates/pages/**`

Then the executor MUST read UI authority docs in this order:

1. `docs/architecture/execution/frontend/UI_EXECUTION_RULES.md`
2. `docs/architecture/execution/frontend/TWIG_TEMPLATE_STANDARDS.md`

Skipping this gate is a process violation for UI execution work.

### UI Authority Boundary (Concise)

- `UI_EXECUTION_RULES.md` is the policy authority.
- `TWIG_TEMPLATE_STANDARDS.md` is the Twig mounting authority.
- `MASTER_OPERATING_PROMPT.md` enforces sequence and cross-references; it does not duplicate detailed frontend policy.

Complete these steps in order before writing any code:

**Step 0 — API Contract (MANDATORY FIRST)**
Check `docs/API/` for a contract file matching this feature.
- Found → read it completely before proceeding to Step 1.
- Not found → write "No contract found" and apply CONFLICT RESOLUTION → No contract found rules.
- When aligning, migrating, or refactoring an existing UI feature to a reference pattern, you MUST retrieve and read the associated `docs/API/{feature}.md` contract FIRST. You MUST NOT apply structural patterns (e.g., `createTable()`) from a reference file if the backend API contract for the target feature does not explicitly return the required data shape (e.g., a paginated envelope vs. a flat array).

**Step 0.5 — Reference Declaration and Conflict Matrix**
You MUST explicitly select a physical reference file as your Source of Truth. You MUST build a mental matrix comparing the required components (e.g., event listeners, table mechanism) against the system rules using the CONFLICT MATRIX defined above. If a `MUST` rule fails the matrix against the reference, you MUST halt and report.

Before outputting any code, you MUST output a block titled **`EVIDENCE OF DESIGN ALIGNMENT`**. This block must state the physical reference file used, the outcome of the Conflict Matrix evaluation, and the exact classification of any discrepancies (CONFLICT, REALITY OVERRIDE, or ACCEPTABLE VARIATION). Outputting code without this block is a critical failure.

**Step 1 — Pattern**
Read `IMPLEMENTATION_CHECKLIST.md` → Step 1. Choose: A / B / C / D.

**Step 2 — Twig Structure**
Read `TWIG_TEMPLATE_STANDARDS.md`. Confirm block structure and script loading order.

**Step 3 — JS Template**
Read `JS_PATTERNS_REFERENCE.md`. Use the copy-ready template for the chosen pattern.

**Step 4 — Table Mechanism**
Read `DATA_TABLE_DOCUMENTATION.md` → Section 3.
Choose: `createTable()` / `ApiHandler.call() + TableComponent()` / manual GET render.

**Step 5 — API Signature**
Read `API_HANDLER_DOCUMENTATION.md`.
Confirm: `ApiHandler.call(endpoint, payload, 'Label', method?)` — 4 parameters.

**Step 6 — Mistake Check**
Read `COMMON_MISTAKES.md`. Verify none of the 12 mistakes appear in the planned implementation.

---

## MANDATORY PRE-CODE QUESTIONNAIRE

Before writing any code, answer all 8 questions.
Every answer must come from the documents — not from assumption or memory.

### If you cannot answer a question, follow this fallback sequence:
1. Read the document listed as source for that question.
2. If the document does not cover this case → check the canonical reference files.
3. If the canonical references do not cover it → check existing production behavior (REALITY).
4. If still uncertain → write: "UNCERTAIN — [reason]" and flag it. Do NOT guess.

---

**Q1 — API Contract**
Does `docs/API/` contain a contract for this feature?
- YES → state the exact endpoint URL(s) from that contract.
- NO → write "No contract found."

Answer: ___

---

**Q2 — Pattern**
Which JS pattern applies to this feature?
Must be one of: A / B / C / D
Source: `IMPLEMENTATION_CHECKLIST.md` → Step 1

Answer: ___

---

**Q3 — Table Mechanism**
Which table mechanism will be used?
Must be exactly one of:
- `createTable()` — POST paginated endpoint
- `ApiHandler.call() + TableComponent()` — POST with custom error handling
- `ApiHandler.call(..., 'GET') + manual HTML` — GET non-paginated endpoint

Source: `DATA_TABLE_DOCUMENTATION.md` → Section 3

Answer: ___

---

**Q4 — Alert Function**
Which alert function will be used?
Must be exactly one of:
- `showAlert('s'/'w'/'d', msg)` — when api_handler.js is NOT in the script list
- `ApiHandler.showAlert('success'/'danger'/'warning'/'info', msg)` — when api_handler.js IS loaded

Source: `IMPLEMENTATION_CHECKLIST.md` → Step 3

Answer: ___

---

**Q5 — Data Attribute**
What is the exact data attribute name for this feature's action buttons?
Must follow the pattern: `data-{feature}-id`

Answer: data-___-id

---

**Q6 — Capabilities Placement**
Where does `window.{feature}Capabilities` go in the Twig template?
Must be: "First element inside `{% block content %}`, before any HTML."

Answer: ___

---

**Q7 — Pagination Mechanism**
How does pagination work with `data_table.js`?
Must reference: `document.addEventListener('tableAction', (e) => { ... })`
Must NOT reference: `window.changePage` or `window.changePerPage`.

Answer: ___

---

**Q8 — Table Reload After Mutation**
How does the table refresh after a successful mutation?
Must reference:
- `window.reload{Feature}Table?.()` called in the actions module
- `window.reload{Feature}Table = () => load{Feature}(currentPage, currentPerPage)` exported from core module

Answer: ___

---

**GATE CHECK**

- All 8 answers confirmed from documents → proceed to PHASE 2.
- Any answer marked "UNCERTAIN" → flag it in the output but proceed with the fallback source noted.
- Any answer that is blank or guessed → STOP. Re-read the relevant document first.

---

### PHASE 2 — GENERATION

- NEW code → MUST follow `HTTP_EXECUTION_RULES.md` (backend) and `UI_EXECUTION_RULES.md` (frontend).
- MODIFIED code → MUST follow `EXECUTION_MIGRATION_STRATEGY.md`.
- LEGACY code → MUST NOT be refactored unless explicitly requested.

---

### PHASE 3 — COMPLIANCE AUDIT

Audit generated code against:
- `HTTP_EXECUTION_RULES.md`
- `UI_EXECUTION_RULES.md`
- `EXECUTION_MIGRATION_STRATEGY.md`
- The feature contract in `docs/API/` (if one exists)

Output:
- Violations (if any)
- Classification: CANONICAL / LEGACY BUT VALID / INVALID
- Risk Level

---

### PHASE 4 — AUTO FIX

Fix ALL violations.
- MUST NOT break behavior.
- MUST respect the Reusable Components Exception.
- MUST NOT introduce unnecessary refactors.

---

### FINAL OUTPUT RULES

1. You MUST perform the Mandatory Pre-Delivery Self-Check.
2. Output the **EVIDENCE OF DESIGN ALIGNMENT** (Reference File & Matrix Result).
3. Output the **SELF-CHECK REPORT**:
   - PASS → proceed to final output.
   - FAIL → list issues, fix them, re-run check.
4. ONLY after PASS on both: output final clean code.
4. If the task included backend endpoint implementation → output the updated or created `docs/API/{feature}.md` as the final artifact after the code.
5. DO NOT include explanations unless requested.
6. DO NOT output intermediate steps unless explicitly asked.

---

## HARD CONSTRAINTS

- NEVER use `json_encode` manually in NEW code.
- NEVER write to response body directly.
- NEVER introduce repository-level transactions in NEW code.
- ALWAYS use `JsonResponseFactory` for responses in NEW code.

---

## EXCEPTION RULE (CRITICAL)

- Reusable services/repositories with mixed responsibilities are VALID.
- DO NOT reject or refactor them automatically.
- Treat them as trusted system components.

---

## EXECUTION SAFETY

If the agent detects ANY conflict between system rules, reference files, or the user prompt, it MUST STOP execution and report the conflict clearly before proceeding. The agent MUST NOT guess, invent custom logic, or silently degrade functionality to bypass the conflict.

If the agent generates code without explicitly printing the **EVIDENCE OF DESIGN ALIGNMENT** block (including the selected reference file and conflict matrix result), the entire execution is deemed **INVALID**. The user will reject the output, and the agent must immediately discard its work, perform the visible analysis, and regenerate the solution.

If the user prompt explicitly commands changes to a directory marked as LOCKED, the agent MUST halt execution, report the conflict, and request explicit `OVERRIDE_LOCK` authorization before writing any code.

If the requested task conflicts with system rules:
- You MUST STOP.
- You MUST report the violation.
- You MUST NOT proceed with generation.

---

## FAILURE CONDITION

If violations are detected:
1. Attempt AUTO FIX first.
2. After AUTO FIX:
   - ALL resolved → continue.
   - ANY persists → STOP. Output: `RULE VIOLATION DETECTED`.

---

## Mandatory Pre-Delivery Self-Check

Before submitting any implementation, perform a full self-audit.
This step is REQUIRED and cannot be skipped.
Audit ONLY the code created or modified in the current task.

---

### BACKEND CHECKS

#### 1. HTTP Rules Compliance
- All responses MUST use `JsonResponseFactory`.
- Query endpoints MUST use `$this->json->data(...)`.
- Command endpoints MUST use `$this->json->success(...)` or `$this->json->data($response, ['success' => true])`.
- `noContent()` MUST NOT be used (LEGACY only).
- POST/PUT/PATCH requests MUST use `$request->getParsedBody()` — never `getQueryParams()`.

#### 2. Validation Rules
- MUST use `ValidationGuard->check(new Schema(), $payload)`.
- MUST NOT use: `ValidatorInterface`, `ValidationManagerInterface`, `SystemErrorMapperInterface`.
- MUST NOT manually handle validation results.

#### 3. Exception Rules
- MUST use domain-specific exceptions.
- MUST NOT use `RuntimeException`.
- Exceptions MUST include meaningful messages.
- Empty exception constructors are NOT allowed.

#### 4. Query System
- MUST use: `ListQueryDTO::fromArray(...)`, `Capabilities::define()`, `ListFilterResolver->resolve(...)`.
- MUST NOT pass raw arrays directly to readers.

#### 5. Permission Mapping
- ALL new routes (`.api` and `.ui`) MUST be registered in `PermissionMapperV2.php`.
- Route names MUST follow: `feature.action.api` / `feature.action.ui`.
- Run `php tools/permission_linter.php` after any routing change.
- Commit MUST NOT proceed unless output states: "All checks passed".

#### 6. Static Analysis (PHPStan)
- All arrays MUST be typed (e.g., `array<string, string>`).
- Generics MUST be annotated.
- Code MUST pass PHPStan `level=max`.

#### 7. Legacy Rule Violations
- MUST NOT use: `noContent()`, `ValidationManagerInterface`, manual JSON handling.

#### 8. Controller Responsibility
- Controller MUST act only as orchestrator.
- MUST NOT contain business logic.
- MUST NOT access DB directly (except transactions).

#### 9. API Contract Output (MANDATORY FOR BACKEND TASKS)
- If the task implemented or modified any endpoint:
  - NEW endpoint in a new module → `docs/API/{feature}.md` was created.
  - NEW endpoint in an existing module → the existing `docs/API/{feature}.md` was updated with the new section.
  - MODIFIED endpoint → the relevant section in `docs/API/{feature}.md` was updated and changes are marked.
- The contract file is the handoff artifact to the frontend. It MUST be delivered in the same task.

---

### UI EXECUTION CHECKS

#### 9.5 Mandatory Reference Verification
Before generating any new UI feature (Twig or JS), the agent MUST identify a structurally similar existing 'Source of Truth' feature in the codebase (e.g., by scanning `app/Modules/AdminKernel/Templates/pages/`) and MUST perfectly replicate its layout grid, data context block, and JS initialization pattern. The agent MUST NOT invent minimalist layout structures.

#### 10. API Contract Compliance
If a contract exists in `docs/API/`:
- Endpoint URLs match exactly what the contract defines.
- Request payload fields match exactly — no extra fields, no missing required fields.
- Response shape is handled correctly per the contract.
- Capability names match exactly what the contract specifies.
- Nothing the contract forbids is present in the implementation.

#### 11. JavaScript Architecture

| Pattern | When | Files |
|---------|------|-------|
| A — Simple Monolith | Read-only, ≤2 actions, <400 lines | 1 file |
| B — Modular | CRUD, modals, multiple actions | `-core.js`, `-modals.js`, `-actions.js`, `-helpers.js` |
| C — GET Static List | Flat array, no pagination, GET endpoint | 1 IIFE file |
| D — Context-Driven | Needs parent IDs from route | 1 file + context object |

- Pattern B modules MUST be wrapped in IIFE: `(function() { 'use strict'; ... })()`.
- Pattern A is VALID — NOT deprecated.

#### 12. API Integration
- ALL API calls MUST use `ApiHandler.call(endpoint, payload, 'Label', method?)`.
- MUST NOT use `fetch()` / `axios` for JSON APIs.
- MUST NOT prepend `/api/` to endpoints.

#### 13. Table System
- POST paginated → `createTable()`.
- POST + custom error → `ApiHandler.call() + TableComponent()`.
- GET non-paginated → `ApiHandler.call(..., 'GET')` + manual HTML. `createTable()` FORBIDDEN.
- If a new UI feature is requested to mimic a standard system table but the target backend endpoint is non-paginated, the agent MUST detect this mismatch and MUST NOT silently downgrade the UI to a manual HTML render. The agent MUST STOP execution and report the mismatch clearly before any further action.
- Pagination → `document.addEventListener('tableAction', ...)`.
- `window.changePage()` / `window.changePerPage()` — NOT called by `data_table.js`. Do NOT rely on them.
- Action buttons → `AdminUIComponents.buildActionButton(...)`. Raw HTML FORBIDDEN.

#### 14. Data Attributes
- Buttons MUST use `data-{feature}-id` — feature-specific.
- `data-entity-id` does NOT exist — FORBIDDEN.

#### 15. Capabilities Enforcement
- `window.{feature}Capabilities` MUST be first element inside `{% block content %}`.
- Syntax: `can_x: {{ capabilities.can_x ?? false ? 'true' : 'false' }}`.
- MUST NOT render actions if capability is `false`.

#### 16. UI State Sync
- After mutation: `window.reload{Feature}Table?.()`.
- `window.reload{Feature}Table` MUST be exported from core module.
- Manual DOM patching FORBIDDEN.

#### 17. Alert System
- Pattern A: `showAlert('s'/'w'/'d', msg)`.
- Pattern B/C/D: `ApiHandler.showAlert('success'/'danger'/'warning'/'info', msg)`.
- Native `alert()` FORBIDDEN.

#### 18. Twig Structure
- `{% block scripts %}` — JS file tags ONLY.
- Inline `<script>` for capabilities/context — inside `{% block content %}`.
- Table container — exactly `<div id="table-container">`.

#### 19. XSS Protection
- `escapeHtml()` MUST be defined and used in every renderer.

#### 20. File Upload Architecture
- No Base64 in JSON payloads.
- `multipart/form-data` for uploads.
- `fetch()` for uploads MUST manually prepend `/api/`.
- Response MUST go through `ApiHandler.parseResponse()`.

---

### BACKEND CONSISTENCY CHECKS

#### 21. Request Validation — MUST use `ValidationGuard`.
#### 22. Response Rules — Query: `json->data(...)`. Command: `json->success(...)`.
#### 23. Exception Rules — MUST include message. No empty constructors.
#### 24. Query System — MUST use `ListQueryDTO`, `Capabilities::define()`, `ListFilterResolver`.

---

### CROSS-LAYER CONSISTENCY CHECKS

#### 25. Full-Stack Integrity
- UI payload MUST match backend schema.
- If a `docs/API/` contract exists — every field must match the contract exactly.
- `page`/`per_page` to a non-paginated GET endpoint is FORBIDDEN.
- Route-scoped IDs MUST NOT be duplicated in the payload.

#### 26. Runtime Payload Simulation
- If HTTP 500 (HTML body): extract PHP error from `result.rawBody`. Fix backend first. Do NOT blind-fix UI.

---

### Output Requirement

SELF-CHECK REPORT before final output:
- PASS → continue.
- FAIL → list issues, fix, re-run.

**No code output is allowed unless ALL checks PASS.**

**Backend tasks: the `docs/API/{feature}.md` contract file is a required delivery artifact alongside the code.**
