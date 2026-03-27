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
- The capability object MUST be explicitly injected into the global window scope via an inline `<script>` block.
- Missing capabilities MUST ALWAYS default to `false` using the null coalescing operator (`?? false ? 'true' : 'false'`).
- Route parameters and parent IDs MUST be injected into Twig as distinct template variables (e.g., `admin_id`, `scope_id`).
- Shared infrastructure scripts (e.g., `api_handler.js`, `data_table.js`) MUST ALWAYS be loaded before feature-specific page scripts using `{% block scripts %}`.

## 4. JavaScript Modular Architecture Rules
- Frontend code MUST follow a modular architecture:
  - `{feature}-with-components.js` (initialization & table rendering)
  - `{feature}-modals.js` (DOM generation)
  - `{feature}-actions.js` (CRUD operations & events)
  - `{feature}-helpers.js` (shared utilities)
- Monolithic JavaScript files are STRICTLY FORBIDDEN for new features.

## 5. JavaScript Table Rendering Rules
- All list rendering MUST use the `createTable(...)` function.
- Custom renderers MUST be passed as an object: `(value, row) => HTML`.
- Inline HTML logic replacing renderers is FORBIDDEN.
- Renderers MUST be used instead of inline HTML generation inside table configuration.

## 6. Shared UI Components Rules
- UI rendering MUST use `AdminUIComponents` utilities (e.g., `renderStatusBadge`).
- Raw HTML string concatenation SHOULD be avoided when helpers exist.

## 7. JavaScript Event, Modal & Form Rules
- Inline `onclick` handlers are STRICTLY FORBIDDEN.
- Event binding MUST ALWAYS use class-based selectors and delegated event listeners attached to parent containers.
- Modals MUST be dynamically generated via JavaScript.
- Twig MUST NOT contain modal HTML for new features.
- Forms MUST NOT call API directly.
- Forms MUST delegate submission to `{feature}-actions.js`.

## 8. API Interaction Contract Rules (Frontend)
- ALL API calls MUST ALWAYS go through `ApiHandler.call(endpoint, payload, operation)`.
- Direct `fetch` / `axios` usage is STRICTLY FORBIDDEN.
- JavaScript MUST ALWAYS call `.query` list API endpoints using the HTTP `POST` method.
- API requests MUST NEVER use `GET` for fetching list data.
- The JS payload structure MUST ALWAYS conform strictly to the API list request schema (`page`, `per_page`, `search: { global: string, columns: object }`).
- Sorting is STRICTLY SERVER-CONTROLLED. UI rules MUST NEVER require `sort` parameters in the payload.
- List response payloads MUST ALWAYS be consumed using the global `createTable()` utility function.

## 9. UI State Synchronization & Lifecycle Rules
- UI MUST NOT simulate state locally. UI MUST rely ONLY on API responses.
- After ANY successful action (create/update/delete/mutation):
  - The UI MUST refresh via `loadData()` to refresh the table state.
- Manual DOM patching is FORBIDDEN.
- UI interaction endpoints mapped in routing MUST ALWAYS end with `.ui` or `.view` as their route name.
- Data query requests from the UI MUST ALWAYS target endpoints ending with `/query`.

## 10. Nested UI & Routing Rules (Frontend)
- When a UI view requires nested data, the parent identifiers MUST be captured from the route (e.g., `/scopes/{scope_id}/domains/{domain_id}`).
- The UI Controller MUST pass these parent IDs directly to the Twig template as scalar variables.
- The Twig template MUST expose these IDs to JavaScript using hidden data attributes (`data-{entity}-id`) or explicitly defined global constants (`window.{entity}Id`).
- Parent IDs MUST be passed from Twig → JS.
- Feature JavaScript MUST ALWAYS extract these parent IDs and append them accurately to the API query payload or endpoint path.
- JS MUST propagate IDs across init → actions → API calls.
- Deep nested IDs MUST NOT be lost or hardcoded.

## 11. Failure & Error Handling Rules (Frontend)
- ALL API errors MUST ALWAYS be caught.
- Errors MUST be passed to `window.showAlert(...)`.
- Silent API failures are STRICTLY FORBIDDEN.
- The UI Controller MUST ALWAYS throw a specific domain exception (e.g., `EntityNotFoundException` or `HttpNotFoundException`) if the requested parent context metadata does not exist.

## 12. Step-Up / 2FA Handling Rules
- In the event of an HTTP 403 (Step-Up authentication required), the 403 Step-Up responses MUST be handled via the `ErrorNormalizer` bridge.
- UI MUST redirect to 2FA flow when required (e.g., `/2fa/verify` route with the correct scope and return path).
- Ignoring Step-Up flows is FORBIDDEN.

## 13. UI vs API Boundary Rules
- UI routes (GET returning HTML) MUST NEVER be treated as APIs.
- UI MUST NOT call UI routes via `ApiHandler` or `fetch`.
- ONLY `/api/*` endpoints are valid for programmatic interaction.

## 14. API Response Shape Rules (Frontend Contract)
- Query responses MUST ALWAYS follow the strict shape: `{ data: [...], pagination: {...} }`.
- UI MUST NOT assume a `success` wrapper or alternative structures for queries.
- Command responses MUST NOT be assumed to contain data payloads.
- Command responses SHOULD be treated as success-only acknowledgements unless a specific API contract explicitly documents returned data.

## 15. Route-Scoped Context Rules
- Identifiers derived from the route context (e.g., `language_id`, `type_id`) MUST NOT be sent in the request payload.
- These values MUST be derived strictly from the URL context.

## 16. No-Assumption Rules
- If a field or behavior is not explicitly defined, it MUST be treated as unsupported.
- UI MUST NOT infer hidden fields, fallback values, or implicit behavior.

## 17. Idempotent Actions Handling Rules
- UI MUST treat certain state-changing endpoints as idempotent (e.g., activate, deactivate, publish, archive).
- UI MUST handle success responses even when no actual state change occurs.
