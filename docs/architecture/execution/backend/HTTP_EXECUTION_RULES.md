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

NOTE: This section represents LEGACY behavior.
For all NEW implementations, refer to Section 9 (Response Behavior Rules), which overrides this section.

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

## 8. Validation Execution Boundary
- Controllers MUST use `ValidationGuard->check(new Schema(), $payload)`
- `ValidationManagerInterface` is DEPRECATED and marked as LEGACY.
- Controllers MUST NOT use `ValidatorInterface`, `ValidationManagerInterface` or `SystemErrorMapperInterface`
- Controllers MUST NOT manually handle validation errors
- `ValidationGuard` is MANDATORY for all new validation logic.

## 9. Response Behavior Rules (OVERRIDES SECTION 4)
- This section EXPLICITLY OVERRIDES Section 4 for all new code.
- Query endpoints MUST return `$this->json->data(...)`.
  - For **Paginated CRUD Lists**, the response MUST contain `data` and `pagination` arrays.
  - For **Non-Paginated/Relational Lists** (e.g., flat arrays mapped to parent entities), the response MAY contain only a `data` array containing the collection.
- Command endpoints MUST return `$this->json->success(...)`
- `noContent()` is DEPRECATED and MUST NOT be used for standard command endpoints.
- `noContent()` is only allowed for legacy backward compatibility.

## 10. Exception Handling Rules
- Controllers MUST use domain-specific exceptions
- `RuntimeException` MUST NOT be used
- Exceptions MUST include context-specific messages
- Empty exception constructors MUST NOT be used unless explicitly allowed

## 11. Static Analysis Constraints (PHPStan)
- All array parameters MUST define strict types (e.g., `array<string, string>`)
- All framework generics MUST be annotated
- **Slim Route Arguments:** Route arguments (`$args` array) are typed as `mixed`. To satisfy maximum static analysis rules, controllers MUST explicitly validate these arguments (e.g., using `is_numeric()`) before attempting to cast them to strict scalar types like `int`. Direct casting like `(int)$args['id']` is FORBIDDEN as it causes "Cannot cast mixed to int" errors.
- Code MUST pass PHPStan `level=max`

## 12. Rule Override Policy
- Newer rules ALWAYS override older conflicting rules in this document.
- Legacy rules (e.g., Section 4) remain for backward compatibility only.
- AI executors MUST always follow the latest defined rule definitions.

## 13. RUNTIME API CONTRACT RULES

### 13.1 Request Contract Strictness
- **Request Payload Parsing:** For all `POST`, `PUT`, and `PATCH` requests expecting JSON or Form Data payloads, **ALWAYS** use `$request->getParsedBody()`. NEVER use `getQueryParams()` unless explicitly reading from the URL query string in a `GET` request.
- **File Upload Parsing:** When handling `multipart/form-data` for file uploads, **ALWAYS** use `$request->getUploadedFiles()` to retrieve the files, do NOT use `getParsedBody()` for the file itself.
- The backend MUST enforce strict payload shapes.
- The backend MUST reject any undocumented or forbidden fields (e.g., `limit`, `filters`, `sort`).
- The backend MUST treat `null` as an explicit value, not as an omitted field, and MUST reject `null` if the field does not explicitly support it.

### 13.2 Route-Scoped Context (Backend)
- Resource identifiers MUST come exclusively from the route context when logically required by the endpoint hierarchy (e.g., `/{type_id}/versions/{document_id}`).
- The backend MUST reject requests where route-scoped identifiers are duplicated or contradicted within the payload.
- Cross-type or cross-scope access attempts MUST return a `404 Not Found` error.

### 13.3 Response Contract (REAL API)
- Query endpoints for **Paginated CRUD Lists** MUST return a strict JSON envelope containing `data` and `pagination` objects.
- Query endpoints for **Non-Paginated/Relational Lists** MUST return a JSON envelope containing the `data` array, but ARE NOT required to return a `pagination` object. The UI must adapt to this using `TableComponent`.
- Command (Mutation/Action) endpoints MUST return a `200 OK` success response using `$this->json->success($response)` or `$this->json->data($response, ['success' => true])`.
- The backend MUST NOT assume the client expects or parses JSON from standard command responses unless explicitly documented.

### 13.4 Frontend API Routing
- **Frontend API Routing:** When using `ApiHandler.call()`, NEVER prepend `/api/` to the endpoint string. The handler automatically resolves the base URL. Use relative feature paths directly (e.g., `products/query`).

### 13.5 Server-Controlled Behavior
- Sorting authority is strictly server-controlled. The backend MUST NOT allow clients to pass arbitrary `sort` or `sort_order` parameters unless explicitly supported by a legacy endpoint.
- Filtering authority rests with the backend schema. Clients MUST NOT bypass column-specific filter definitions.
- The backend MUST enforce these controls and reject client attempts to override server-defined list behaviors.
- The backend MUST reject unsupported implicit parameters like `page` or `per_page` if the endpoint is designed as a non-paginated static list. UI implementations MUST NOT forcibly inject these parameters via tools like `createTable`.

### 13.6 Scope Enforcement (Backend)
- The backend MUST enforce strict boundary scopes for all nested resources (e.g., a version MUST belong to the specified document type).
- Invalid scope or cross-scope access MUST return a `404 Not Found` exception, not a partial success or `403`.

### 13.7 Lifecycle Rules (Domain Behavior)
- The backend MUST treat state-changing lifecycle endpoints (e.g., activate, deactivate, publish, archive) as idempotent.
- Applying a state change to an entity already in that state MUST result in a successful `200 OK` (no-op) response without throwing an error.

### 13.8 Capability Awareness (Backend)
- The backend MUST enforce all authorization and permission checks server-side.
- The backend MUST NOT trust UI capability flags as a security layer.
- Endpoints MUST independently verify the caller's capabilities regardless of UI state.

### 13.9 No-Assumption Principle (Backend)
- The backend MUST reject implicit fields, undefined behavior, or silent defaults.
- If a behavior, payload field, or state transition is not explicitly defined by the API contract, the backend MUST treat it as unsupported and reject the request.
