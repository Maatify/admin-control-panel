# HTTP Execution Rules

## 1. Purpose
This document enforces strict, non-negotiable execution rules for the HTTP layer, standardizing request and response behavior, transaction boundaries, and error orchestration.

## 2. Scope (New code only)
These rules apply to all new API and UI controllers. Legacy implementations are tolerated but MUST NOT be copied into new features. When modified, they SHOULD be refactored to comply with these rules.

## 3. Response Handling Rules
- Controllers MUST inject and use `Maatify\AdminKernel\Http\Response\JsonResponseFactory` for all JSON responses.
- Controllers MUST NOT use `json_encode()` manually.
- Controllers MUST NOT manually construct response payloads via array mutation before serialization.
- Controllers MUST NOT write directly to the response body stream (`$response->getBody()->write()`).
- `JsonResponseFactory` is the single source of truth for all HTTP JSON responses.

## 4. Action Response Rules
- Simple, state-changing actions returning no meaningful data MUST call `$this->json->noContent($response)` to return an HTTP 204.
- Complex actions returning created entities or credentials MUST encapsulate the data in a dedicated Response DTO and call `$this->json->data($response, $dto)` to return an HTTP 200.
- Actions MUST NOT return arbitrary unstructured JSON arrays or scalar variables.

## 5. Error Handling Rules
- Controllers MUST throw specific, typed Domain Exceptions or HTTP Exceptions upon encountering invalid state or logic failures.
- Controllers MUST NOT manually construct error responses or attempt to serialize error objects.
- Controllers MUST NOT catch exceptions merely to convert them into a custom JSON response.
- The global `ErrorMiddleware` is solely responsible for catching exceptions and mapping them into the unified JSON error envelope using `JsonResponseFactory`.
- Controllers MUST NOT swallow exceptions; all caught exceptions MUST be re-thrown.

## 6. Transaction Rules
- Database transactions MUST be controlled explicitly at the controller level when orchestrating multiple state-changing operations across distinct repositories.
- Repositories MUST NOT initiate, commit, or roll back transactions internally.
- Controllers MUST wrap transactional blocks in a `try...catch (\Throwable)` structure.
- The `catch` block MUST roll back the transaction and immediately re-throw the original exception to preserve the error handling pipeline.

## 7. Controller Execution Rules
- The Controller acts strictly as an execution orchestrator.
- Controllers MUST NOT contain intrinsic business logic, complex data transformations, or domain rule evaluations.
- Controllers MUST NOT contain direct SQL queries or invoke PDO directly outside of transaction management.
- Controllers MUST delegate validation, contextual lookups, capability resolution, and data persistence to injected services, repositories, or guards.