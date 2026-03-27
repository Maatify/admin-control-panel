# UI Interaction Depth Analysis

## 1. Hierarchical Route Structure
Observed API route definitions reveal varying levels of structural nesting.

**Flat Routing (Level 1):**
- `/sessions/query` (`sessions.list.api`)
- `/admins/query` (`admins.list.api`)

**Deeply Nested Hierarchical Routing (Level 3+):**
- `/i18n/scopes/{scope_id:[0-9]+}/domains/query` (`i18n.scopes.domains.query.api`)
- `/i18n/scopes/{scope_id:[0-9]+}/domains/{domain_id:[0-9]+}/keys/query` (`i18n.scopes.domains.keys.query.api`)
- `/i18n/scopes/{scope_id:[0-9]+}/domains/{domain_id:[0-9]+}/translations/query` (`i18n.scopes.domains.translations.query.api`)
- `/i18n/scopes/{scope_id:[0-9]+}/coverage/languages/{language_id:[0-9]+}` (`i18n.scopes.coverage.domain.api`)

## 2. Interaction Depth Levels
The depth of UI interaction directly corresponds to the URL structure necessary to fulfill the request.

- **Level 1 (Single Context):** UI views like Admins or Sessions load a flat list. The UI controller (`UiAdminsController`) resolves capabilities once, and the frontend (`admins-list.js`) queries a flat API endpoint (`/admins/query`) requiring no parent identifiers.
- **Level 2 (Parent-Child Context):** A UI view requiring a parent resource identifier to load children (e.g., `/i18n/scopes/{scope_id}/domains/query`). The UI must retain or fetch the `scope_id` context to query the domains.
- **Level 3 (Grandparent-Parent-Child Context):** A UI view querying nested elements (e.g., `/i18n/scopes/{scope_id}/domains/{domain_id}/keys/query`). The frontend must maintain state across two parental layers (`scope_id` and `domain_id`) before fetching the target resource (`keys`).

## 3. Request Chaining Flow
In hierarchically nested features, data retrieval requires sequential dependencies.

**Observed Chaining Pattern for `i18n.scopes.domains.keys.query.api`:**
1. UI requests Scope context (`scope_id`).
2. UI requests Domain context within that Scope (`domain_id`).
3. UI requests Keys belonging to both the `scope_id` and `domain_id`.

If a user navigates to a deep interaction view, the frontend JavaScript must orchestrate these parameters, constructing API paths that satisfy the strict routing requirements defined in `I18nApiRoutes.php`.

## 4. UI → API Dependency Model
The UI layer is heavily dependent on specific API hierarchies.

- **Flat Dependencies:** `sessions.js` depends solely on `/api/sessions/query`. Capabilities like `can_revoke_id` dictate button visibility, triggering parallel flat endpoints (`/api/sessions/{id}`).
- **Nested Dependencies:** A frontend implementing I18n translations depends on a specific parameter lineage (`scope_id` -> `domain_id` -> `keys`). The UI logic must be structured to supply these IDs correctly to match the defined route paths.

## 5. Performance Implications (Observed)
- **Flat Endpoints:** Require only single database queries orchestrated by readers (e.g., `PdoSessionListReader`), filtering based on independent payload DTOs.
- **Hierarchical Endpoints:** Enforce route-level validation of contextual relationships (e.g., matching a `domain_id` to a specific `scope_id`). This implies that the respective API controllers or underlying readers must validate these multi-tier relationships during query execution (UNVERIFIED: precise reader implementations for I18n queries).
- Fetching deep data may require sequential API calls if the frontend lacks the parent IDs upfront, contrasting with flat endpoints where single, immediate payload submissions are sufficient.

## 6. Unsafe To Generalize
- It is unsafe to assume all UI views interact with flat API routes. I18n routes demonstrate multi-tier nesting requiring complex state management.
- It is unsafe to assume frontend requests map one-to-one with database tables without considering the mandatory URL path parameters (like `{scope_id}`).
- It is unsafe to generalize the authorization enforcement of flat routes (e.g. `sessions.view_all` inline check) to nested routes, where capability scope might be inherited or strictly tied to parent resource ownership (UNVERIFIED).