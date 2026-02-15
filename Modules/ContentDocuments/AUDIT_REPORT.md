# Audit Report — ContentDocuments

## ✅ Confirmed Solid Areas
*   **Schema Isolation & Integrity:** Schema is self-contained, uses proper FKs, unique constraints, and safe cascading rules (RESTRICT on acceptance, CASCADE on translations).
*   **Enforcement Logic:** `DocumentEnforcementService::enforcementResult` uses O(M+N) memory differencing to avoid N+1 queries during high-traffic enforcement checks.
*   **Lifecycle Management:** `DocumentLifecycleService` correctly implements the Create -> Publish -> Activate workflow with transaction safety for `activate` (deactivate-all + activate-one).
*   **Actor-Agnostic Design:** `ActorIdentity` Value Object decouples the module from specific user/customer tables, enabling universal usage.
*   **Immutable Entities:** `Document`, `DocumentType`, `DocumentTranslation`, and `DocumentAcceptance` are correctly implemented as `readonly` entities.

## ⚠️ Weaknesses / Risk Areas
*   **Mutable Value Object (`ActorIdentity`):** The `ActorIdentity` class is `final` but has `public` properties that are not `readonly`, allowing mutability after instantiation (`Modules/ContentDocuments/Domain/ValueObject/ActorIdentity.php`).
*   **Facade Entity Construction:** `ContentDocumentsFacade::saveTranslation` constructs an "anemic" `DocumentTranslation` entity with `id: 0` and `updatedAt: null` to bypass repository hydration, creating tight coupling to the repository's `ON DUPLICATE KEY UPDATE` implementation (`Modules/ContentDocuments/Application/Service/ContentDocumentsFacade.php`).
*   **N+1 Query Risk:** `DocumentQueryService::getVersionsWithLanguage` iterates through versions and executes a separate translation query for each, causing N+1 performance degradation (`Modules/ContentDocuments/Application/Service/DocumentQueryService.php`).
*   **App-Side "One Active" Enforcement:** The `documents` table relies on application logic (`deactivateAllByTypeId`) to ensure only one active version per type. A race condition or direct DB manipulation could violate this invariant as there is no partial unique index on `(document_type_id, is_active=1)`.

## ❌ Critical Issues (if any)
*   None.

## 🧠 Architectural Completeness Score (0–100%)
*   95%

## 📌 Extraction Safety Verdict
*   SAFE
