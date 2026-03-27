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
- Avoid mimicking surrounding legacy patterns (such as manual `json_encode` or repository-level transactions) when extending the system.