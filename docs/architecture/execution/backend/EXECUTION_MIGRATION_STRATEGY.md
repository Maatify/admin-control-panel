# Execution Migration Strategy

## 1. Purpose
This document defines the strict behavioral strategy for handling the structural mismatch between the existing codebase and the new execution standards defined in `HTTP_EXECUTION_RULES.md`.

## 2. Definitions
- **Legacy Code:** Existing controllers, repositories, or services implemented before the new execution rules were introduced.
- **New Code:** Any new controller, route, repository, or service being added to the application.
- **Modified Code:** Existing legacy code that is being altered or extended for new functionality.

## 3. Rules Application Strategy
- New features MUST strictly follow all rules defined in `HTTP_EXECUTION_RULES.md`.
- Legacy code is tolerated in its current state and MUST NOT block isolated new feature development.
- Modified legacy code SHOULD be upgraded to comply with `HTTP_EXECUTION_RULES.md` when the scope of modification is significant enough to warrant refactoring.
- Upgrading MUST NOT introduce breaking changes to existing behavior.

## 4. Response Handling Strategy
- New features MUST inject and use `JsonResponseFactory` for all JSON responses.
- Legacy implementations relying on manual `json_encode()` and direct body writes are allowed to remain.
- Legacy implementations MUST NOT be extended with further manual JSON encoding; new logic within legacy files SHOULD transition to `JsonResponseFactory`.

## 5. Transaction Strategy
- New transactional boundaries MUST be explicitly managed at the Controller level.
- New repositories MUST NOT initiate, commit, or rollback transactions internally.
- Legacy repositories managing transactions internally are tolerated until they are refactored.

## 6. Error Handling Strategy
- New features MUST exclusively use an Exception-driven error flow, relying on `ErrorMiddleware` to format the unified JSON response.
- New controllers MUST NOT manually catch exceptions to construct custom JSON error payloads.
- Legacy controllers swallowing exceptions and returning manual JSON error structures are tolerated but MUST NOT be used as templates or copied into new implementations.

## 7. AI Behavior Rules
When generating code, AI executors MUST:
- Detect the context of the current task (creating new components vs. modifying legacy components).
- Choose the correct execution pattern strictly aligned with `HTTP_EXECUTION_RULES.md` for all new files.
- AI MUST NOT mimic surrounding legacy patterns (such as manual `json_encode` or repository-level transactions) when generating new logic.

Priority Order:
1. HTTP_EXECUTION_RULES.md
2. EXECUTION_MIGRATION_STRATEGY.md
3. Existing code patterns (lowest priority)

## 8. Reusable Components Exception
- Existing services or repositories that expose both query and action methods are allowed if they are already used safely across the system.
- AI MUST NOT reject or refactor such components solely based on architectural purity.
- When using such components:
  - Query methods MAY be used directly if no dedicated Reader exists.
  - The component MUST be treated as a trusted abstraction.
- New code SHOULD prefer proper separation (Reader vs Repository), but MUST NOT break or duplicate existing reusable components.
- AI MUST NOT enforce artificial separation if it results in:
  - Code duplication
  - Loss of reuse
  - Increased complexity
