# Acceptance & Enforcement

The primary utility of this module is determining *who* needs to accept *what*.

## Enforcement Logic (`DocumentEnforcementService`)

The `enforcementResult(ActorIdentity $actor)` method performs the following logic:

1.  **Find Candidates:** Query all `documents` where:
    *   `is_active` = 1
    *   `is_published` = 1
    *   `requires_acceptance` = 1

2.  **Fetch History:** Query `document_acceptance` for this `$actor`.

3.  **Diff:** Return any Candidate that does *not* have a matching History record (matching `document_id` + `version`).

This ensures that if you release `v2.0` of Terms, all users who accepted `v1.0` will immediately see `v2.0` as "Required".

## Recording Acceptance (`DocumentAcceptanceService`)

The `accept()` method is strictly guarded. It will throw `InvalidDocumentStateException` if:
*   The document is not Published.
*   The document is not Active.
*   The document does not Require Acceptance.

This prevents users from accepting "Draft" documents or documents that have been deprecated.

### Idempotency
If an actor attempts to accept the same document version twice, the service detects the existing record (via `DocumentAlreadyAcceptedException` or lookup) and returns the original timestamp, ensuring the API is idempotent.
