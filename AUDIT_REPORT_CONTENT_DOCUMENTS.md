🔍 Audit Report — ContentDocuments

✅ Confirmed Solid Areas

*   **Database Integrity**: `active_guard` generated column with UNIQUE constraint `uq_active_type_guard` enforces "One Active Document Per Type" at the database level.
*   **Domain Immutability**: `Document`, `DocumentType`, and ValueObjects (`DocumentVersion`, `ActorIdentity`, `DocumentTypeKey`) are `final readonly` and enforce strict invariants.
*   **Lifecycle Management**: `DocumentLifecycleService::activate` correctly uses transactions to deactivate all versions of a type before activating the new one, preventing race conditions.
*   **Acceptance Logic**: `DocumentAcceptanceService` strictly enforces `isPublished` and `isActive` checks before recording acceptance.
*   **Legal Compliance**: `document_acceptance` table stores `version` redundantly and uses `INSERT IGNORE` / `ON DUPLICATE KEY` protection via `uq_actor_document_version`.
*   **Actor Agnostic Design**: `ActorIdentity` value object and `actor_type`/`actor_id` columns ensure total decoupling from User/Auth tables.
*   **Optimized Queries**: `DocumentQueryService::getVersionsWithLanguage` uses `findByDocumentIdsAndLanguage` to prevent N+1 queries when fetching translations for a list.

⚠️ Weaknesses / Risk Areas

*   **Data Redundancy**: The `documents` table stores both `document_type_id` (FK) and `type_key` (string). While this optimizes reads, it creates a denormalization risk if `document_types.key` is ever updated (data inconsistency).
*   **Facade Logic Leakage**: `ContentDocumentsFacade::saveTranslation` contains upsert logic (check existence -> create vs update) that belongs in a Domain Service (e.g., `DocumentTranslationService`).
*   **Entity Redundancy**: `Document` entity constructor accepts both `documentTypeId` and `typeKey`, which mirrors the database redundancy and relies on the repository to keep them in sync.

❌ Critical Issues

*   **Violation of Version Immutability (Legal Risk)**
    *   **File:** `Modules/ContentDocuments/Application/Service/ContentDocumentsFacade.php` (method `saveTranslation`)
    *   **Description:** The `saveTranslation` method allows updating translation content (`title`, `content`) for *any* document, regardless of its state (`isActive`, `publishedAt`, `archivedAt`).
    *   **Impact:** A legally accepted document version can have its content silently altered without changing the version string, invalidating user acceptance and legal audit trails.

🧠 Architectural Completeness Score
92%

📌 Extraction Safety Verdict
SAFE
(The module is fully decoupled and actor-agnostic. The critical issue is a logic bug, not a structural dependency problem.)
