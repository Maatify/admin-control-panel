# FEATURE_EXECUTION_REALITY.md

## 1. Purpose
This document defines the strictly observed, real-world execution flow for API and UI features based on empirical code extraction (Sessions LIST, Admins QUERY, Permissions QUERY). It focuses solely on direct observations from the codebase.

## 2. Scope & Authority Boundaries
This document covers the boundaries of request handling, input validation, DTO transformation, capability resolution, and database reading for LIST/QUERY operations across UI and API endpoints.

## 3. Execution Overview
A request moves from the route matcher through permission validation to response generation in the following sequence:
1. Route matches request (`*Routes.php`).
2. `AuthorizationGuardMiddleware` maps transport keys (`.api`, `.ui`) to a canonical permission via `PermissionMapperV2.php` and executes an authorization check.
3. Controller is executed.
   - **For UI:** `UiPermissionService` evaluates specific permissions and injects them into Twig as capability flags.
   - **For API:** Validation step using `SharedListQuerySchema` before DTO construction into a `ListQueryDTO`, mapped through domain `ListCapabilities` into `ResolvedListFilters`, and passed to a `ReaderRepositoryInterface`.
4. Reader performs database queries (see Reader implementations) and returns a DTO.
5. Controller serializes the Response DTO to JSON or renders a Twig view.

## 4. API Query Execution Flow
The step-by-step pipeline for API list endpoints is observed uniformly:
1.  **Validation:** Validation step using `SharedListQuerySchema` before DTO construction.
2.  **DTO Conversion:** `ListQueryDTO::fromArray()` formats the input.
3.  **Capabilities Definition:** A specific `ListCapabilities` configuration is loaded (e.g., `AdminListCapabilities::define()`, `PermissionsCapabilities::define()`).
4.  **Filter Resolution:** `ListFilterResolver->resolve()` accepts the `ListQueryDTO` and `ListCapabilities` and returns `ResolvedListFilters`.
5.  **Reader Invocation:** The controller invokes a specific interface (e.g., `SessionListReaderInterface->getSessions()`) passing the `ListQueryDTO` and `ResolvedListFilters`.
6.  **Response Generation:** The reader returns a concrete response DTO (e.g., `SessionListResponseDTO`) that the controller serializes via `json_encode`.

## 5. Route & Permission Mapping
API route names use the `.api` suffix, and UI route names use the `.ui` suffix.
-   **Supporting File Paths:**
    -   `app/Modules/AdminKernel/Http/Routes/Api/Features/SessionsApiRoutes.php`
    -   `app/Modules/AdminKernel/Http/Routes/Api/Features/AdminsApiRoutes.php`
    -   `app/Modules/AdminKernel/Http/Routes/Api/Features/PermissionsApiRoutes.php`
    -   `app/Modules/AdminKernel/Http/Routes/Ui/Features/SessionsUiRoutes.php`
    -   `app/Modules/AdminKernel/Http/Routes/Ui/Features/AdminsUiRoutes.php`

These suffixes act as transport keys mapped directly to a single canonical permission (e.g., `sessions.list`, `admins.list`, `permissions.query`) defined in `PermissionMapperV2.php`.
-   **Supporting File Paths:**
    -   `app/Modules/AdminKernel/Domain/Security/PermissionMapperV2.php`

`AuthorizationGuardMiddleware` intercepts the request, reads the route name, resolves it against `PermissionMapperV2`, and passes the resolved canonical requirement to `AuthorizationService`.
-   **Supporting File Paths:**
    -   `app/Modules/AdminKernel/Http/Middleware/AuthorizationGuardMiddleware.php`
    -   `app/Modules/AdminKernel/Http/Routes/Api/ApiProtectedRoutes.php`

## 6. Validation & DTO Flow
Validation step using `SharedListQuerySchema` before DTO construction. Validated request payloads are passed to the static constructor `ListQueryDTO::fromArray()`.
-   **Supporting File Paths:**
    -   `Modules/Validation/Schemas/SharedListQuerySchema.php`
    -   `app/Modules/AdminKernel/Domain/List/ListQueryDTO.php`
    -   `app/Modules/AdminKernel/Http/Controllers/Api/Sessions/SessionQueryController.php`
    -   `app/Modules/AdminKernel/Http/Controllers/Api/Admin/AdminQueryController.php`
    -   `app/Modules/AdminKernel/Http/Controllers/Api/Permissions/PermissionsController.php`

## 7. Filter Resolution & Capabilities
A `ListCapabilities` instance is either passed dynamically or instantiated (e.g. `AdminListCapabilities::define()`). This defines acceptable query column configurations. It is passed along with the `ListQueryDTO` into `ListFilterResolver->resolve()`, resulting in a `ResolvedListFilters` object.
-   **Supporting File Paths:**
    -   `app/Modules/AdminKernel/Infrastructure/Query/ListFilterResolver.php`
    -   `app/Modules/AdminKernel/Domain/List/ListCapabilities.php`
    -   `app/Modules/AdminKernel/Domain/List/AdminListCapabilities.php`
    -   `app/Modules/AdminKernel/Domain/List/PermissionsCapabilities.php`
    -   `app/Modules/AdminKernel/Http/Controllers/Api/Sessions/SessionQueryController.php`
    -   `app/Modules/AdminKernel/Http/Controllers/Api/Admin/AdminQueryController.php`
    -   `app/Modules/AdminKernel/Http/Controllers/Api/Permissions/PermissionsController.php`

## 8. Reader & Data Access Pattern
A domain-specific reader interface (implemented by a PDO reader) receives the `ListQueryDTO` and `ResolvedListFilters`. The reader performs database queries (see Reader implementations) and returns a domain-specific ResponseDTO containing an array of item DTOs and a `PaginationDTO`.
-   **Supporting File Paths:**
    -   `app/Modules/AdminKernel/Infrastructure/Reader/Session/PdoSessionListReader.php`
    -   `app/Modules/AdminKernel/Infrastructure/Reader/Admin/PdoAdminQueryReader.php`
    -   `app/Modules/AdminKernel/Infrastructure/Reader/PDOPermissionsReaderRepository.php`
    -   `app/Modules/AdminKernel/Domain/DTO/Session/SessionListResponseDTO.php`
    -   `app/Modules/AdminKernel/Domain/DTO/AdminList/AdminListResponseDTO.php`
    -   `app/Modules/AdminKernel/Domain/DTO/Permission/PermissionsQueryResponseDTO.php`

## 9. UI Execution Flow (Observed)
UI Controllers explicitly query the `UiPermissionService` with specific string keys representing discrete actions (e.g. `sessions.revoke.id`, `admins.profile.view`). The controllers construct an array of capabilities, which is passed to the Twig view data to determine frontend rendering states.
-   **Supporting File Paths:**
    -   `app/Modules/AdminKernel/Application/Security/UiPermissionService.php`
    -   `app/Modules/AdminKernel/Http/Controllers/Ui/SessionListController.php`
    -   `app/Modules/AdminKernel/Http/Controllers/Ui/Admin/UiAdminsController.php`

## 10. Feature-Specific Variations
-   **Inline Hard Data Scope Check:** `AuthorizationService->hasPermission()` is called inline within the API controller to determine if the query should be restricted by `$adminIdFilter` before calling the reader repository.
    -   **Observed in:** Sessions LIST (`app/Modules/AdminKernel/Http/Controllers/Api/Sessions/SessionQueryController.php`)
-   **Best-effort Telemetry:** The API controller attempts to write an audit trail mapping the query shape, result count, and request context using `DiagnosticsTelemetryService` wrapped in a swallowed `try/catch`.
    -   **Observed in:** Sessions LIST (`app/Modules/AdminKernel/Http/Controllers/Api/Sessions/SessionQueryController.php`)

## 11. Canonical vs Observed Reality
-   **Observed behavior aligns with:** `docs/architecture/security/PERMISSION_STRATEGY.md`. `PermissionMapperV2.php` Observed mapping between route names and permissions via PermissionMapperV2.php separating routing transport definitions from canonical business capabilities. Observed: used in UI Controllers for `UiPermissionService`. Observed: used in API Controllers for `AuthorizationService`.
-   **No direct canonical document reference identified:** The exact execution pipeline for querying lists (Validation step using SharedListQuerySchema -> `ListQueryDTO` -> `ListCapabilities` -> `ListFilterResolver` -> `ResolvedListFilters` -> `Reader`) is a consistent structural querying pattern across API list routes.

## 12. Unsafe To Generalize
The following items are specific implementations that must not be abstracted into global layers based on observed code:
-   Extracting the "Data Scope Check" (e.g., `sessions.view_all`) into a generalized middleware or abstract repository logic. The Sessions feature handles this explicitly within its specific API controller domain logic.
-   Adding `DiagnosticsTelemetryService` event recording indiscriminately to all `ListQuery` controllers.
-   Standardizing UI Capability mapping string names. They are highly feature-specific (e.g., `can_revoke_id`, `can_view_admin`).

## 13. Action Execution Flow (Observed)

### 1. Shared Steps
- **Route Registration & Permission Mapping:** API routes use the `.api` suffix (e.g., `languages.create.api`, `admin.create.api`), mapped to canonical permissions (e.g., `languages.create`, `admin.create`) in `PermissionMapperV2.php` and enforced by `AuthorizationGuardMiddleware`.
- **Request Validation:** The controller extracts the raw array payload using `getParsedBody()` and validates it via `ValidationGuard` using a specific schema object (e.g., `LanguageCreateSchema`, `AdminCreateSchema`).
- **Manual Data Extraction:** Controllers manually map scalar values (e.g., `is_string`, `is_bool`, `array_key_exists`, `trim`) directly from the validated array.

### 2. Simple Actions (Observed)
- Observed:
  Controller calls LanguageManagementService with scalar and enum arguments (e.g., `LanguageManagementService`).
- The controller passes individual scalar values and enumerated types (e.g., `TextDirectionEnum`) as separate arguments to the service method.
- Returns an empty JSON response with an HTTP 200 status.

### 3. Complex Actions (Observed)
- Observed:
  Controller directly calls multiple repositories and services in sequence, directly interacting with multiple Repositories (`AdminRepository`, `AdminEmailRepository`, `AdminPasswordRepositoryInterface`) and Services (`AdminIdentifierCryptoServiceInterface`, `PasswordService`).
- Observed:
  Controller calls:
  - AdminRepository->create()
  - AdminEmailRepository->addEmail()
  - AdminPasswordRepositoryInterface->savePassword()
  in sequence.
- Returns a populated Response DTO (`AdminCreateResponseDTO`) serialized to JSON.

### 4. DTO Usage (Observed)
- Comprehensive Request DTOs representing the entire validated payload are not used in either action. Data is handled as a structured array or scalar variables.
- Partial Request DTOs are used for specific, complex nested data structures (e.g., `CreateAdminEmailRequestDTO` for email validation).
- Observed:
  - Admin CREATE returns AdminCreateResponseDTO
  - Languages CREATE returns HTTP 200 with no body.

### 5. Transaction Handling (Observed)
- Observed:
  Transaction is started and committed using PDO inside the controller when coordinating multiple repository insertions.
- The sequence starts with `$this->pdo->beginTransaction()`, executes multiple repository writes, concludes with `$this->pdo->commit()`, and utilizes a `catch (\Throwable $e)` block to trigger `$this->pdo->rollBack()`.
- No global or middleware-based transaction management was observed for these actions.

### 6. Notes / Unsafe To Generalize
- It is unsafe to assume the existence of a dedicated "Creation Service" for every entity; complex creations may be orchestrated directly inside the controller.
- It is unsafe to assume that every POST payload maps to a single Request DTO; manual array parsing is prevalent.
- It is unsafe to assume that transactions are handled implicitly or within repository layers; they are explicitly declared in the controller when required.