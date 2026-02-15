# 🔍 Audit Report — ContentDocuments

## ✅ Confirmed Solid Areas
*   **Domain Integrity**: `Document`, `DocumentAcceptance`, `DocumentTranslation`, and `DocumentType` are `final readonly` entities, strictly enforcing immutability.
*   **Value Objects**: `ActorIdentity`, `DocumentVersion`, and `DocumentTypeKey` enforce strong invariants (format, length) and are used consistently.
*   **Schema Safety**: `uq_documents_one_active_per_type` (with `active_guard` generated column) provides a bulletproof database-level guarantee for "One Active Document Per Type".
*   **Lifecycle Management**: `DocumentLifecycleService::activate` correctly handles concurrency by deactivating all other versions in a transaction before activation.
*   **Acceptance Flow**: `DocumentAcceptanceService` and `PdoDocumentAcceptanceRepository` correctly enforce "Actor-Agnostic" design and prevent duplicate acceptances via `uq_actor_document_version`.
*   **Performance**: `DocumentEnforcementService` uses O(1) array lookups for actor acceptances, avoiding N+1 query risks.
*   **Facade Completeness**: `ContentDocumentsFacade` exposes all necessary read/write operations for the Website and Admin Panel (creation, publishing, activation, translation).
*   **Transaction Safety**: `PdoTransactionManager` prevents nested transaction errors by checking state before execution.

## ⚠️ Weaknesses / Risk Areas
*   **Missing Delete Capability**: The module provides no mechanism (Service or Facade) to delete document versions (e.g., drafts created in error), leading to potential accumulation of junk data (`DocumentLifecycleService`).
*   **Rigid Type Management**: `ContentDocumentsFacade` intentionally excludes `DocumentType` creation or update methods (`requires_acceptance_default`, `is_system`), forcing reliance on external seeders or direct DB manipulation.
*   **Immutable Draft Configuration**: Once a `Document` version is created, its `requires_acceptance` flag cannot be modified (no `update` method in Repository or Service), requiring a new version to correct mistakes.
*   **Hidden Coupling in Translation Save**: While safe, the Facade's `saveTranslation` logic redundantly fetches the entity to get the ID, masking the Repository's underlying "Upsert by Natural Key" capability (`PdoDocumentTranslationRepository`).

## ❌ Critical Issues
*   (None)

## 🧠 Architectural Completeness Score
*   95%

## 📌 Extraction Safety Verdict
*   SAFE
