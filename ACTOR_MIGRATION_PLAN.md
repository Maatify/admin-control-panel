# Actor Model Migration Plan

## Phase 3: Actor Infrastructure Activation

*   **Objective**
    *   Enable `ActorContext` to be reliably populated from the existing authoritative `AdminContext` (via Request Attributes) without modifying legacy flows.
    *   Ensure `ActorProviderInterface` returns a valid Actor for all authenticated Admin requests.

*   **Files / Layers Affected**
    *   `App\Http\Middleware\ActorContextMiddleware` (Logic update)
    *   `routes/web.php` (Middleware registration)
    *   `App\Bootstrap\Container` (Verification of wiring only)

*   **Preconditions**
    *   Phases 1 & 2 (Context classes and generic middleware) are merged.

*   **Why this phase is SAFE**
    *   `ActorContext` is not yet consumed by any business logic or DTOs.
    *   Changes are strictly additive or isolated to the `ActorContextMiddleware` which is currently unused/optional.
    *   No existing `AdminContext` logic is touched.

*   **What this phase MUST NOT touch**
    *   `AdminContext` class or `AdminContextMiddleware`.
    *   Any existing DTOs or Controllers.

*   **Exit Criteria**
    *   `ActorContextMiddleware` correctly retrieves `AdminContext` from Request Attributes (not Container).
    *   `ActorContextMiddleware` is registered in the middleware stack *inner* to `AdminContextMiddleware`.
    *   `ActorProviderInterface::getActor()` returns a valid `Actor` (Type: ADMIN) during an active admin session.
    *   `ActorProviderInterface::getActor()` throws or behaves predictably when no actor is present (e.g. public routes), matching the "No implicit actors" rule.

## Phase 4: Producer-Side Preparation (Dependency Injection)

*   **Objective**
    *   Equip all data producers (Controllers, Services, Jobs) with the ability to retrieve the current Actor.
    *   Prepare the "Producers" before modifying the "Consumers" (DTOs).

*   **Files / Layers Affected**
    *   `App\Http\Controllers\*` (Admin-facing controllers)
    *   `App\Domain\Service\*`
    *   DI Container definitions (if manual wiring is required)

*   **Preconditions**
    *   Phase 3 is verified (Actor is available in the context).

*   **Why this phase is SAFE**
    *   We are only injecting the `ActorProviderInterface`.
    *   We are NOT yet passing the actor to any DTOs or logic.
    *   Runtime behavior remains identical.

*   **What this phase MUST NOT touch**
    *   DTO definitions.
    *   Existing method signatures of Services (unless optional).

*   **Exit Criteria**
    *   All target Controllers/Services have `ActorProviderInterface` injected.
    *   Unit tests verify that these classes can successfully call `getActor()`.

## Phase 5: Hybrid DTO Introduction (Optionality)

*   **Objective**
    *   Introduce Actor awareness to DTOs in a non-breaking manner.
    *   Allow DTOs to *accept* an Actor without *requiring* it, preventing the "immediate runtime breakage" seen previously.

*   **Files / Layers Affected**
    *   `App\Domain\DTO\*` (Target DTOs like `AuditLogDTO`, `CommandDTO`, etc.)

*   **Preconditions**
    *   Phase 4 is merged (Producers have the Actor ready to pass).

*   **Why this phase is SAFE**
    *   The new Actor field is nullable (`?Actor`) and has a default value (`null`).
    *   Existing code (`new DTO(...)`) continues to compile and run without changes.
    *   No behavior change; the Actor is simply ignored if not present.

*   **What this phase MUST NOT touch**
    *   The `required` status of the field (must remain optional).
    *   Any code instantiating the DTOs.

*   **Exit Criteria**
    *   Target DTOs have a `public ?Actor $actor = null` property (or constructor argument).
    *   Static analysis confirms no broken instantiation sites.

## Phase 6: Data Flow Connection (Propagation)

*   **Objective**
    *   Update all Producers to actually pass the Actor to the DTOs.
    *   Achieve 100% coverage of Actor propagation for the target DTOs.

*   **Files / Layers Affected**
    *   `App\Http\Controllers\*`
    *   `App\Domain\Service\*`

*   **Preconditions**
    *   Phase 5 is merged (DTOs accept the Actor).

*   **Why this phase is SAFE**
    *   DTOs already accept the argument.
    *   We are strictly adding the argument to the constructor calls.
    *   If `getActor()` fails (which it shouldn't per Phase 3), the exception is clear and indicates an infrastructure gap, not a DTO mismatch.

*   **What this phase MUST NOT touch**
    *   DTO definition (remains optional for now).
    *   `AdminContext` usage (legacy compatibility).

*   **Exit Criteria**
    *   All instantiation sites of target DTOs are updated to pass `actor: $this->actorProvider->getActor()`.
    *   Runtime verification confirms DTOs now hold the Actor object.

## Phase 7: Strict Enforcement & Verification

*   **Objective**
    *   Make the Actor mandatory for the DTOs.
    *   Permanently forbid `(ADMIN, NULL)` states (where an Admin action has no Actor).

*   **Files / Layers Affected**
    *   `App\Domain\DTO\*`

*   **Preconditions**
    *   Phase 6 verified (All call sites are updated).

*   **Why this phase is SAFE**
    *   We have guaranteed that all callers provide the Actor in Phase 6.
    *   The change is purely to the DTO signature, locking in the correctness.

*   **What this phase MUST NOT touch**
    *   `AdminContext` (still needed for legacy middleware/auth).

*   **Exit Criteria**
    *   DTO constructor signatures are updated to `Actor $actor` (non-nullable, no default).
    *   Compiler / Static Analysis confirms validity.
    *   Attempting to create a DTO without an actor is now a compile-time (or immediate runtime) error.

## Phase 8: Legacy Retirement (Long Term)

*   **Objective**
    *   Remove `AdminContext` and rely solely on `ActorContext`.

*   **Files / Layers Affected**
    *   `App\Http\Middleware\AdminContextMiddleware`
    *   `App\Context\AdminContext`

*   **Preconditions**
    *   All business logic and DTOs rely exclusively on `Actor`.
    *   `ActorContextMiddleware` is updated to hydrate directly from Auth mechanism (Session/JWT) instead of relying on `AdminContext`.

*   **Why this phase is SAFE**
    *   Redundant context is removed only after it is unused.

*   **What this phase MUST NOT touch**
    *   `ActorContext` (it is now the authority).

*   **Exit Criteria**
    *   `AdminContext` class is deleted.
    *   `AdminContext` middleware is removed.
