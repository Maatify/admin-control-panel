# UI Execution Rules (DRAFT)

## 1. UI Controller Rules
- A UI Controller MUST use `__invoke(Request $request, Response $response): Response` for single-action views.
- A UI Controller MUST use specific action methods (e.g., `index`, `profile`) ONLY when grouping related read-only views for a single entity type.
- A UI Controller MUST ONLY extract route parameters, fetch basic parent context metadata, resolve capabilities, and render a Twig template.
- A UI Controller MUST NEVER fetch list data or iterate over domain entities for table rendering.
- A UI Controller MUST NEVER execute state-changing business logic or process form submissions natively.

## 2. Capability System Rules
- The `$capabilities` array MUST be explicitly assembled in the UI Controller using `UiPermissionService::hasPermission()`.
- The array keys MUST be boolean flags prefixed with `can_` (e.g., `can_create`, `can_update`, `can_delete`).
- The capabilities MUST ALWAYS be passed to the Twig template under the strict key name `capabilities`.
- The capability object in JavaScript MUST be strictly named `window.{feature}Capabilities` (e.g., `window.adminsCapabilities`, `window.languagesCapabilities`).
- `window.{feature}Capabilities` MUST be consumed on JavaScript initialization.
- UI MUST NOT render actions if capability is `false`.
- The Actions column MUST ALWAYS be conditionally injected/removed from the renderers object if the viewing capability is `false`, NOT just visually hidden.

## 3. Twig Rules
- The capability object MUST be explicitly injected into the global window scope via an inline `<script>` block using exact syntax: `window.{feature}Capabilities = {{ capabilities|json_encode|raw }};`. Using `const` or `let` is FORBIDDEN.
- Missing capabilities MUST ALWAYS default to `false` using the null coalescing operator (`?? false ? 'true' : 'false'`).
- Route parameters and parent IDs MUST be injected into Twig as distinct template variables (e.g., `admin_id`, `scope_id`).
- Shared infrastructure scripts (e.g., `api_handler.js`, `data_table.js`) MUST ALWAYS be loaded before feature-specific page scripts using `{% block scripts %}`.

## 4. JavaScript Modular Architecture Rules
- Frontend code MUST follow a modular architecture:
  - `{feature}-with-components.js` (initialization & table rendering). This file MUST ONLY handle rendering logic.
  - `{feature}-modals.js` (DOM generation)
  - `{feature}-actions.js` (CRUD operations & events). ALL DOM event listeners (click, submit) MUST be centralized here using global `document.addEventListener` delegation.
  - `{feature}-helpers.js` (shared utilities)
- Monolithic JavaScript files are STRICTLY FORBIDDEN for new features.

## 5. JavaScript Table Rendering Rules
- **Data Table Initialization:** NEVER fetch data manually via `ApiHandler` to feed into `TableComponent` for standard CRUD lists. **ALWAYS** use the global `createTable(apiUrl, payload, ...)` function from `data_table.js`. `TableComponent` is strictly for static data rendering.
- List rendering MUST manually fetch data via `ApiHandler.call()` and pass the raw data payload to `TableComponent(...)`. *(DEPRECATED: See Data Table Initialization rule above)*
- The feature MUST globally export `window.changePage(page)` and `window.changePerPage(perPage)` to bridge pagination events back to the feature's data loading method.
- Custom renderers MUST be passed as an object: `(value, row) => HTML`.
- Inline HTML logic replacing renderers is FORBIDDEN.
- Renderers MUST be used instead of inline HTML generation inside table configuration.

## 6. Shared UI Components Rules
- UI rendering MUST use `AdminUIComponents` utilities (e.g., `renderStatusBadge`).
- You MUST inspect existing usages of `AdminUIComponents` (e.g., in `languages-with-components.js`) to verify exact function signatures and expected object configurations before usage. DO NOT assume parameters.
- You MUST strictly use `AdminUIComponents.buildActionButton(...)` and `AdminUIComponents.SVGIcons` to generate action buttons in table renderers. Raw HTML string concatenation for action buttons is FORBIDDEN.

## 7. JavaScript Event, Modal & Form Rules
- Inline `onclick` handlers are STRICTLY FORBIDDEN.
- All DOM event delegation for actions MUST be handled using the reusable `setupButtonHandler(selector, callback, options)` function abstraction exactly as patterned in `languages-helpers.js`.
- **UI Component Data Attributes:** **STRICT STANDARD:** All action buttons and interactive UI elements referencing a database record MUST use `data-entity-id="{id}"`. Scripts setting up event handlers via `setupButtonHandler` MUST bind to `data-entity-id`. Avoid using arbitrary `data-id` or custom attributes for primary identification.
- Manual `document.addEventListener` loops inside `{feature}-actions.js` are FORBIDDEN for buttons.
- Modals MUST be injected exactly once into `document.body` via `insertAdjacentHTML` during module initialization within `{feature}-modals.js`. They MUST be toggled via CSS classes (e.g., `hidden`), rather than recreated dynamically on every button click.
- Twig MUST NOT contain modal HTML for new features.
- Forms MUST NOT call API directly.
- Forms MUST delegate submission to `{feature}-actions.js`.

## 8. API Interaction Contract Rules (Frontend)
- ALL API calls MUST ALWAYS go through `ApiHandler.call(endpoint, payload, operation)`.
- The third argument to `ApiHandler.call(endpoint, payload, operation)` MUST be a descriptive, user-readable action string (e.g., 'Update Product', 'Toggle Status'). It MUST NOT be an HTTP verb (e.g., 'POST', 'GET').
- Direct `fetch` / `axios` usage is STRICTLY FORBIDDEN. (Exception: File uploads via `multipart/form-data`, but MUST parse response via `ApiHandler.parseResponse()`).
- **File Upload API Pathing:** When using `fetch` directly (e.g., for file uploads), you MUST manually prepend `/api/` to the endpoint URL, as `fetch` does not resolve the base path automatically like `ApiHandler.call` does.
- JavaScript MUST ALWAYS call `.query` list API endpoints using the HTTP `POST` method.
- API requests MUST NEVER use `GET` for fetching list data.
- The JS payload structure MUST ALWAYS conform strictly to the API list request schema (`page`, `per_page`, `search: { global: string, columns: object }`).
- Sorting is STRICTLY SERVER-CONTROLLED. UI rules MUST NEVER require `sort` parameters in the payload.
- List response payloads MUST ALWAYS be consumed using the global `TableComponent(...)` utility function. *(DEPRECATED: Use `createTable`)*

## 9. UI Documentation & Baseline Adherence
- Before implementing any frontend component or script, you MUST consult the documentation files located in `public/assets/maatify/admin-kernel/js/docs/`.
  - NOTE: You MUST explicitly IGNORE the directory `public/assets/maatify/admin-kernel/js/docs/Admin_CRUD_Builder`.
- All UI implementations MUST adhere to the design and baseline constraints defined in `docs/ADMIN_KERNEL_EXCEPTION_BASELINE_AUDIT.md`.
- Failure to incorporate instructions from these explicit documentation sources constitutes a violation of execution rules.

## 10. UI State Synchronization & Lifecycle Rules
- UI MUST NOT simulate state locally. UI MUST rely ONLY on API responses.
- After ANY successful action (create/update/delete/mutation):
  - The UI MUST refresh via `loadData()` to refresh the table state.
- Manual DOM patching is FORBIDDEN.
- UI interaction endpoints mapped in routing MUST ALWAYS end with `.ui` or `.view` as their route name.
- Data query requests from the UI MUST ALWAYS target endpoints ending with `/query`.

## 11. Nested UI & Routing Rules (Frontend)
- When a UI view requires nested data, the parent identifiers MUST be captured from the route (e.g., `/scopes/{scope_id}/domains/{domain_id}`).
- The UI Controller MUST pass these parent IDs directly to the Twig template as scalar variables.
- The Twig template MUST expose these IDs to JavaScript using hidden data attributes (`data-{entity}-id`) or explicitly defined global constants (`window.{entity}Id`).
- Parent IDs MUST be passed from Twig → JS.
- Feature JavaScript MUST ALWAYS extract these parent IDs and append them accurately to the API query payload or endpoint path.
- JS MUST propagate IDs across init → actions → API calls.
- Deep nested IDs MUST NOT be lost or hardcoded.

## 12. Failure & Error Handling Rules (Frontend)
- ALL API errors MUST ALWAYS be caught.
- **User Notifications:** NEVER write custom notification logic or use native `alert()`. **ALWAYS** use the globally injected `window.ApiHandler.showAlert(type, message)` or `showNotification` wrapper to ensure consistent styling, `z-index` layering, and error normalization.
- Errors MUST be passed to `window.showAlert(...)`.
- Silent API failures are STRICTLY FORBIDDEN.
- The UI Controller MUST ALWAYS throw a specific domain exception (e.g., `EntityNotFoundException` or `HttpNotFoundException`) if the requested parent context metadata does not exist.

## 13. Step-Up / 2FA Handling Rules
- In the event of an HTTP 403 (Step-Up authentication required), the 403 Step-Up responses MUST be handled via the `ErrorNormalizer` bridge.
- UI MUST redirect to 2FA flow when required (e.g., `/2fa/verify` route with the correct scope and return path).
- Ignoring Step-Up flows is FORBIDDEN.

## 14. UI vs API Boundary Rules
- UI routes (GET returning HTML) MUST NEVER be treated as APIs.
- UI MUST NOT call UI routes via `ApiHandler` or `fetch`.
- ONLY `/api/*` endpoints are valid for programmatic interaction.

## 15. API Response Shape Rules (Frontend Contract)
- Query responses MUST ALWAYS follow the strict shape: `{ data: [...], pagination: {...} }`.
- UI MUST NOT assume a `success` wrapper or alternative structures for queries.
- Command responses MUST NOT be assumed to contain data payloads.
- Command responses SHOULD be treated as success-only acknowledgements unless a specific API contract explicitly documents returned data.

## 16. Route-Scoped Context Rules
- Identifiers derived from the route context (e.g., `language_id`, `type_id`) MUST NOT be sent in the request payload.
- These values MUST be derived strictly from the URL context.

## 17. No-Assumption Rules
- If a field or behavior is not explicitly defined, it MUST be treated as unsupported.
- UI MUST NOT infer hidden fields, fallback values, or implicit behavior.

## 18. Idempotent Actions Handling Rules
- UI MUST treat certain state-changing endpoints as idempotent (e.g., activate, deactivate, publish, archive).
- UI MUST handle success responses even when no actual state change occurs.

## 19. File Upload Architecture Rules
- Base64 encoding MUST NEVER be embedded within general JSON update payloads.
- Dedicated API endpoints (e.g., `/update-image`) expecting `multipart/form-data` MUST be used.
- Uploads MUST use native `fetch` with `FormData`.
- Responses from `fetch` MUST be passed through `ApiHandler.parseResponse()` for consistent error handling and notification logic.
