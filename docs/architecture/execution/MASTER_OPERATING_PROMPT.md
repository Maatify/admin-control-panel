You are in FULL EXECUTION SYSTEM MODE.

You are NOT allowed to operate outside this system.

---

SOURCE OF TRUTH (MANDATORY):

You MUST strictly follow these documents:

1. docs/architecture/execution/HTTP_EXECUTION_RULES.md
2. docs/architecture/execution/EXECUTION_MIGRATION_STRATEGY.md
3. docs/architecture/execution/FEATURE_EXECUTION_REALITY.md
4. docs/architecture/execution/UI_INTERACTION_DEPTH_ANALYSIS.md
5. docs/architecture/execution/EXECUTION_ENVIRONMENT_RULES.md
6. docs/architecture/execution/UI_EXECUTION_RULES.md

---

GLOBAL BEHAVIOR:

- You MUST prioritize REALITY over theoretical architecture
- You MUST NOT blindly enforce rules if they break existing behavior
- You MUST distinguish between:
  - NEW code
  - LEGACY code
  - MODIFIED code
- If a pattern is repeatedly used across the codebase, it MUST be treated as VALID even if it conflicts with theoretical architecture

---

EXECUTION PIPELINE (MANDATORY):

### PHASE 1 — CLASSIFICATION
- Classify the task:
  - NEW
  - LEGACY
  - MODIFIED

- Classification is internal and MUST NOT be included in final output

---

### PHASE 2 — GENERATION
- Generate implementation

Rules:
- NEW → MUST follow HTTP_EXECUTION_RULES.md
- MODIFIED → MUST follow EXECUTION_MIGRATION_STRATEGY.md
- LEGACY → MUST NOT be refactored unless requested

---

### PHASE 3 — COMPLIANCE AUDIT
- Audit the generated code against:

  - HTTP_EXECUTION_RULES.md
  - UI_EXECUTION_RULES.md
  - EXECUTION_MIGRATION_STRATEGY.md

Output:

- Violations (if any)
- Classification:
  - CANONICAL
  - LEGACY BUT VALID
  - INVALID
- Risk Level

---

### PHASE 4 — AUTO FIX
- Fix ALL violations

Rules:
- MUST NOT break behavior
- MUST respect Reusable Components Exception
- MUST NOT introduce unnecessary refactors

---

### FINAL OUTPUT RULES:

1. You MUST perform the Mandatory Pre-Delivery Self-Check.

2. You MUST output the SELF-CHECK REPORT first:
   - PASS → proceed to final output
   - FAIL → list issues, fix them, and re-run check

3. ONLY after PASS:
   - Output final clean code/result

4. DO NOT include explanations unless requested
5. DO NOT output intermediate steps unless explicitly asked
---

HARD CONSTRAINTS:

- NEVER use json_encode manually in NEW code
- NEVER write to response body directly
- NEVER introduce repository-level transactions in NEW code
- ALWAYS use JsonResponseFactory for responses in NEW code

---

EXCEPTION RULE (CRITICAL):

- Reusable services/repositories with mixed responsibilities are VALID
- DO NOT reject or refactor them automatically
- Treat them as trusted system components

---

### EXECUTION SAFETY

- If the requested task conflicts with system rules or would introduce architectural violations:
  - You MUST STOP
  - You MUST report the violation
  - You MUST NOT proceed with generation

---

FAILURE CONDITION:

If violations are detected:

1. You MUST attempt AUTO FIX first

2. After AUTO FIX:
   - If ALL violations are resolved → continue execution
   - If ANY violation still persists:

     → STOP
     → Output: "RULE VIOLATION DETECTED"

---

## Mandatory Pre-Delivery Self-Check

Before submitting any implementation, the executor MUST perform a full self-audit.

This step is REQUIRED and cannot be skipped.

---

### Scope

Audit ONLY the code created or modified in the current task.

---

### Required Checks

#### 1. HTTP Rules Compliance
- All responses MUST use JsonResponseFactory
- Query endpoints MUST use $this->json->data(...)
- Command endpoints MUST use $this->json->success(...)
- noContent() MUST NOT be used (LEGACY only)

---

#### 2. Validation Rules
- MUST use ValidationGuard->check(new Schema(), $payload)
- MUST NOT use:
  - ValidatorInterface
  - ValidationManagerInterface
  - SystemErrorMapperInterface
- MUST NOT manually handle validation results

---

#### 3. Exception Rules
- MUST use domain-specific exceptions
- MUST NOT use RuntimeException
- Exceptions MUST include meaningful messages
- Empty exception constructors are NOT allowed

---

#### 4. Query System (if applicable)
- MUST use:
  - ListQueryDTO::fromArray(...)
  - Capabilities::define()
  - ListFilterResolver->resolve(...)
- MUST NOT pass raw arrays directly to readers

---

#### 5. Permission Mapping
- ALL new routes MUST be registered in PermissionMapperV2.php
- Route names MUST follow: feature.action.api

---

#### 6. Static Analysis (PHPStan)
- All arrays MUST be typed (e.g., array<string, string>)
- Generics MUST be annotated
- Code MUST pass PHPStan level=max

---

#### 7. Legacy Rule Violations
- MUST NOT use:
  - noContent()
  - ValidationManagerInterface
  - manual JSON handling

---

#### 8. Controller Responsibility
- Controller MUST act only as orchestrator
- MUST NOT contain business logic
- MUST NOT access DB directly (except transactions)

---

### UI EXECUTION CHECKS

#### 9. JavaScript Architecture
- MUST follow modular structure:
  - `{feature}-with-components.js`
  - `{feature}-modals.js`
  - `{feature}-actions.js`
  - `{feature}-helpers.js`
- Monolithic JS is FORBIDDEN

#### 10. API Integration (Frontend)
- MUST use `ApiHandler.call(...)`
- MUST NOT use `fetch` / `axios` directly
- MUST use `POST` for all API interactions

#### 11. Table System
- MUST use `createTable()`
- MUST use renderers `(value, row) => HTML`
- MUST NOT use inline HTML inside config
- MUST NOT use inline `onclick` handlers

#### 12. Capability Enforcement
- MUST consume `window.{feature}Capabilities`
- MUST NOT render UI actions if capability is false

#### 13. UI State Sync
- MUST call `loadData()` after mutations
- MUST NOT manually patch DOM

#### 14. Error Handling
- MUST catch ALL API errors
- MUST use `window.showAlert(...)`

#### 15. Routing Context
- MUST pass route IDs correctly
- MUST NOT duplicate route IDs in payload

---

### BACKEND CONSISTENCY CHECKS

#### 16. Request Validation
- MUST use `ValidationGuard`
- MUST NOT manually validate

#### 17. Response Rules
- Query → `json->data(...)`
- Command → `json->success(...)`

#### 18. Exception Rules
- MUST include message
- MUST NOT use empty constructors

#### 19. Query System
- MUST use:
  - `ListQueryDTO`
  - `Capabilities::define()`
  - `ListFilterResolver`

---

### CROSS-LAYER CONSISTENCY CHECKS

#### 20. Full-Stack Integrity
- UI payload MUST match backend schema
- UI MUST NOT send unsupported fields
- Backend MUST reject invalid payloads
- Route-scoped IDs MUST NOT be duplicated

---

### Output Requirement

Executor MUST produce a SELF-CHECK REPORT before final output:

- PASS → continue
- FAIL → list issues, fix them, and re-run check

---

### Final Constraint

No code output is allowed unless ALL checks PASS.
