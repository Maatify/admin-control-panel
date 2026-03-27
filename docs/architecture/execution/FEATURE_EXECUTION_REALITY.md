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

## 14. UI Query Execution Flow (Observed)

### 1. Route & Controller
- A UI request matches a route ending with the `.ui` suffix (e.g., `/sessions` matches `sessions.list.ui`, `/admins` matches `admins.list.ui`).
- `AuthorizationGuardMiddleware` resolves the route name to a canonical permission using `PermissionMapperV2.php` and executes an authorization check.
- The associated UI controller (e.g., `SessionListController`, `UiAdminsController`) is invoked.
- **Supporting File Paths:**
  - `app/Modules/AdminKernel/Http/Routes/Ui/Features/SessionsUiRoutes.php`
  - `app/Modules/AdminKernel/Http/Routes/Ui/Features/AdminsUiRoutes.php`
  - `app/Modules/AdminKernel/Http/Controllers/Ui/SessionListController.php`
  - `app/Modules/AdminKernel/Http/Controllers/Ui/Admin/UiAdminsController.php`

### 2. Permission Resolution (UI Layer)
- The UI controller calls `UiPermissionService->hasPermission()` explicitly for each UI capability required on the page (e.g., `sessions.revoke.id`, `sessions.revoke.bulk`, `admin.create.api`, `admins.profile.view`).
- These capabilities are grouped into a `$capabilities` array.
- **Supporting File Paths:**
  - `app/Modules/AdminKernel/Application/Security/UiPermissionService.php`

### 3. Twig Rendering
- The UI controller calls the Twig renderer, passing the `$capabilities` array (and any other necessary data) to the template.
- The Twig template outputs the UI shell and injects the capabilities into the frontend environment via a global JavaScript object (e.g., `window.sessionsCapabilities`).
- **Supporting File Paths:**
  - `app/Modules/AdminKernel/Templates/pages/sessions.twig`

### 4. Frontend Trigger (JS)
- A specific page-level JavaScript file (e.g., `sessions.js`, `admins-list.js`) initializes on page load.
- The JS file attaches event listeners for user input (search, filters, pagination).
- It triggers an initial data load by calling an underlying table rendering mechanism (UNVERIFIED: source of `createTable` function).
- **Supporting File Paths:**
  - `public/assets/maatify/admin-kernel/js/pages/sessions.js`
  - `public/assets/maatify/admin-kernel/js/pages/admins-list.js`

### 5. API Interaction
- The JavaScript function issues an asynchronous POST request to the corresponding API query endpoint (e.g., `/api/sessions/query`, `/api/admins/query`).
- This request triggers the standard "API Query Execution Flow" defined in Section 4.

### 6. Data Rendering Cycle
- The API responds with JSON containing a data array (`*ListItemDTO`s) and pagination metadata.
- The JS file receives this JSON and executes custom rendering functions (e.g., `statusRenderer`, `sessionIdRenderer`, `actionsRenderer`).
- During rendering, the JS explicitly checks the injected `window.*Capabilities` object to determine whether to show or hide specific action buttons (e.g., Revoke, Edit, Delete).
- **Supporting File Paths:**
  - `public/assets/maatify/admin-kernel/js/pages/sessions.js`

## 15. UI Interaction Depth (Observed)

### 1. Hierarchical Feature Structure
Observed API route definitions reveal varying levels of structural nesting.

**Flat Routing (Level 1):**
- `/sessions/query` (`sessions.list.api`)
- `/admins/query` (`admins.list.api`)

**Deeply Nested Hierarchical Routing (Level 3+):**
- `/i18n/scopes/{scope_id:[0-9]+}/domains/query` (`i18n.scopes.domains.query.api`)
- `/i18n/scopes/{scope_id:[0-9]+}/domains/{domain_id:[0-9]+}/keys/query` (`i18n.scopes.domains.keys.query.api`)
- `/i18n/scopes/{scope_id:[0-9]+}/domains/{domain_id:[0-9]+}/translations/query` (`i18n.scopes.domains.translations.query.api`)
- `/i18n/scopes/{scope_id:[0-9]+}/coverage/languages/{language_id:[0-9]+}` (`i18n.scopes.coverage.domain.api`)

### 2. Interaction Levels
The depth of UI interaction directly corresponds to the URL structure necessary to fulfill the request.

- **Level 1 (Single Context):** UI views like Admins or Sessions load a flat list. The UI controller (`UiAdminsController`) resolves capabilities once, and the frontend (`admins-list.js`) queries a flat API endpoint (`/admins/query`) requiring no parent identifiers.
- **Level 2 (Parent-Child Context):** A UI view requiring a parent resource identifier to load children (e.g., `/i18n/scopes/{scope_id}/domains/query`). The UI must retain or fetch the `scope_id` context to query the domains.
- **Level 3 (Grandparent-Parent-Child Context):** A UI view querying nested elements (e.g., `/i18n/scopes/{scope_id}/domains/{domain_id}/keys/query`). The frontend must maintain state across two parental layers (`scope_id` and `domain_id`) before fetching the target resource (`keys`).

### 3. Request Chaining Flow
In hierarchically nested features, data retrieval requires sequential dependencies.

**Observed Chaining Pattern for `i18n.scopes.domains.keys.query.api`:**
1. UI requests Scope context (`scope_id`).
2. UI requests Domain context within that Scope (`domain_id`).
3. UI requests Keys belonging to both the `scope_id` and `domain_id`.

If a user navigates to a deep interaction view, the frontend JavaScript must orchestrate these parameters, constructing API paths that satisfy the strict routing requirements defined in `I18nApiRoutes.php`.

### 4. UI → API Dependency Model
The UI layer is heavily dependent on specific API hierarchies.

- **Flat Dependencies:** `sessions.js` depends solely on `/api/sessions/query`. Capabilities like `can_revoke_id` dictate button visibility, triggering parallel flat endpoints (`/api/sessions/{id}`).
- **Nested Dependencies:** A frontend implementing I18n translations depends on a specific parameter lineage (`scope_id` -> `domain_id` -> `keys`). The UI logic must be structured to supply these IDs correctly to match the defined route paths.

### 5. Performance Implications (Observed)
- **Flat Endpoints:** Require only single database queries orchestrated by readers (e.g., `PdoSessionListReader`), filtering based on independent payload DTOs.
- **Hierarchical Endpoints:** Enforce route-level validation of contextual relationships (e.g., matching a `domain_id` to a specific `scope_id`). This implies that the respective API controllers or underlying readers must validate these multi-tier relationships during query execution (UNVERIFIED: precise reader implementations for I18n queries).
- Fetching deep data may require sequential API calls if the frontend lacks the parent IDs upfront, contrasting with flat endpoints where single, immediate payload submissions are sufficient.

### 6. Unsafe To Generalize
- It is unsafe to assume all UI views interact with flat API routes. I18n routes demonstrate multi-tier nesting requiring complex state management.
- It is unsafe to assume frontend requests map one-to-one with database tables without considering the mandatory URL path parameters (like `{scope_id}`).
- It is unsafe to generalize the authorization enforcement of flat routes (e.g. `sessions.view_all` inline check) to nested routes, where capability scope might be inherited or strictly tied to parent resource ownership (UNVERIFIED).

## 16. UI Navigation Model (Observed)

### 1. Flat UI (Client-driven)
- UI routes like `/admins` (`admins.list.ui`) or `/sessions` load a primary shell via Twig.
- Navigation (pagination, filtering, sorting) within these views is entirely client-driven, managed by frontend JavaScript (`admins-list.js`) fetching from flat API endpoints (`/admins/query`).
- State is managed within the browser session/URL parameters rather than relying on deep path hierarchy.

### 2. Nested UI (Server-driven)
- The I18n UI module demonstrates a server-driven navigation model where user drill-down requires rendering distinct Twig views matching a strict URL hierarchy.
- For example, drilling into a Scope requires a full page load to `/i18n/scopes/{scope_id}` (`i18n.scopes.details.ui`).

### 3. Route Hierarchy (Observed)
Observed UI route definitions require specific URL contexts to navigate between logical layers.

- **Level 1 (List):** `/i18n/scopes` (`i18n.scopes.list.ui`)
- **Level 2 (Detail):** `/i18n/scopes/{scope_id:[0-9]+}` (`i18n.scopes.details.ui`)
- **Level 3 (Nested Summary):** `/i18n/scopes/{scope_id:[0-9]+}/domains/{domain_id:[0-9]+}/keys` (`i18n.scopes.domains.keys.ui`)
- **Level 3 (Nested Detail):** `/i18n/scopes/{scope_id:[0-9]+}/domains/{domain_id:[0-9]+}/translations` (`i18n.scopes.domains.translations.ui`)

### 4. Hybrid Behavior
- Even within deeply nested, server-driven UI views (e.g., loading the shell for `i18n.scopes.domains.translations.ui`), the final data rendering relies on client-driven API interactions targeting correspondingly nested API endpoints (e.g., `i18n.scopes.domains.translations.query.api`).
- The Twig layer is responsible for injecting the necessary parent IDs (e.g., `scope_id`, `domain_id`) into the frontend context so JavaScript can construct the correct API query path.

### 5. Unsafe To Generalize
- It is unsafe to assume the entire application follows a single page application (SPA) paradigm; deep navigation often forces full HTTP GET requests for distinct Twig controllers.
- It is unsafe to assume URL paths are purely cosmetic; they strictly define the required context (`{scope_id}`, `{domain_id}`) passed to the underlying UI Controllers.

## 17. Validation Reality
- Validation is enforced via `ValidationGuard`
- Validation MUST throw exceptions
- Controllers MUST NOT handle validation results manually

## 18. Query System Reality
- List endpoints MUST use:
  - `ListQueryDTO`
  - `Capabilities::define()`
  - `ListFilterResolver->resolve()`
- If underlying service does not support this pattern:
  - Controllers MUST adapt WITHOUT bypassing validation and filtering

## 19. Permission Mapping Reality
- Every new route defined via `->setName(...)` MUST be registered in `PermissionMapperV2.php`
- Missing mappings will fail CI (permission-lint)
