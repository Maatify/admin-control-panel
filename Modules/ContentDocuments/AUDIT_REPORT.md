# 🔍 Audit Report — ContentDocuments

## ✅ Confirmed Solid Areas

*   **Domain Immutability**: All Entities (`Document`, `DocumentType`, etc.) and Value Objects (`DocumentVersion`, `ActorIdentity`) use `final readonly` and strictly enforce invariants.
*   **Decoupled Acceptance Tracking**: `document_acceptance` table correctly avoids Foreign Keys to actor tables, ensuring the module remains actor-agnostic.
*   **Lifecycle Management**: `DocumentLifecycleService` correctly manages the `createVersion -> publish -> activate` flow with transaction boundaries.
*   **Enforcement Layer**: `DocumentEnforcementService` uses efficient in-memory diffing to avoid N+1 queries during acceptance checks.
*   **Schema Integrity**: Foreign Keys and `ON DELETE` rules are correctly configured (`RESTRICT` for accepted documents, `CASCADE` for translations).

## ⚠️ Weaknesses / Risk Areas

*   **Hidden Coupling in Translation Save**: `PdoDocumentTranslationRepository::save` relies on `INSERT ... ON DUPLICATE KEY UPDATE` and ignores the provided Entity ID. The `ContentDocumentsFacade` implicitly couples to this by initializing new translations with `id: 0`.
*   **Soft Enforcement of Active Flag**: The "One Active Document Per Type" rule is enforced purely by `DocumentLifecycleService` logic. The `documents` table lacks a partial unique index on `(document_type_id, is_active=1)`, technically allowing multiple active versions if the DB is manipulated directly.
*   **Dormant Broken Contract**: `DocumentQueryServiceInterface` is registered in the container but unused in the main application flow, masking the critical bugs contained within its implementation.

## ❌ Critical Issues (if any)

*   **Hydration Data Corruption**: In `Modules/ContentDocuments/Infrastructure/Persistence/MySQL/PdoDocumentRepository.php`, the method `findVersionsWithTranslationsByTypeAndLanguage` executes `SELECT documents.*, document_translations.*`. Due to column name collision (specifically `id`), the hydrated `Document` entity receives the `id` of the `document_translation` instead of the `document`. This corrupts the entity identity.
*   **Broken Service Logic**: In `Modules/ContentDocuments/Application/Service/DocumentQueryService.php`, the method `getVersionsWithLanguage` consumes the corrupted entities from the buggy repository method and then attempts to fetch translations using the wrong ID, guaranteeing failure or incorrect data.

## 🧠 Architectural Completeness Score (0–100%)
**85%**

## 📌 Extraction Safety Verdict
**CONDITIONAL**
(Safe for extraction only if the `DocumentQueryService` and the buggy repository method `findVersionsWithTranslationsByTypeAndLanguage` are removed or patched. The Core Facade and Lifecycle services are safe.)
