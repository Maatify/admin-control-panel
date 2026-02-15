# Lifecycle Management

Managing documents involves a strict state machine to ensure users are never presented with incomplete data.

## The State Machine

1.  **Draft**
    *   Created via `createVersion`.
    *   `published_at` is NULL.
    *   `is_active` is 0.
    *   Invisible to `EnforcementService`.
    *   Editable (content/translations).

2.  **Published**
    *   Transition via `publish()`.
    *   `published_at` is set.
    *   `is_active` is 0.
    *   Ready to be viewed, but not yet enforced on users.

3.  **Active**
    *   Transition via `activate()`.
    *   `is_active` is 1.
    *   **Side Effect:** The service automatically sets `is_active = 0` for all *other* versions of the same Document Type.
    *   Visible to `EnforcementService`.

## The "One Active Version" Rule
The `DocumentLifecycleService::activate()` method wraps the operation in a transaction:
1.  Deactivate all versions for this Type.
2.  Activate the target version.

This ensures that at any point in time, a generic query for "Current Terms of Service" returns exactly one (or zero) result.
