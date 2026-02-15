# 🔍 Audit Report — ContentDocuments

## ✅ Confirmed Solid Areas
*   **Schema Integrity**: `document_types` keys, `documents` constraints (including `active_guard` unique constraint), and `document_acceptance` immutability are robust.
*   **Domain Model**: Value Objects (`ActorIdentity`, `DocumentVersion`, `DocumentTypeKey`) are immutable and enforce invariants. Entities are `readonly`.
*   **Lifecycle Enforcement**: `DocumentLifecycleService` works with DB constraints to strictly enforce "Create → Publish → Activate" and "One Active Per Type".
*   **Concurrency Safety**: `active_guard` generated column prevents race conditions on activation at the database level. `PdoTransactionManager` prevents nested transaction errors.
*   **Repository Safety**: `PdoDocumentRepository` avoids hydration collisions (selects `documents.*`). `PdoDocumentTranslationRepository` handles upserts explicitly.
*   **Enforcement Layer**: `DocumentEnforcementService` uses efficient bulk-fetching to avoid N+1 queries.

## ⚠️ Weaknesses / Risk Areas
*   **Dormant Code**: `DocumentQueryService` (and its interface) contains useful logic (`getVersionsWithLanguage`) but is currently unused by the Facade, leading to potential code rot.
*   **Implicit Version Matching**: `DocumentEnforcementService` relies on string concatenation (`id . '|' . version`) for matching, which assumes strict format consistency between `documents` and `document_acceptance` tables (low risk but brittle).
*   **Repository Isolation Risk**: `PdoDocumentRepository::activate` updates the flag without checking for other active documents. While the DB constraint protects integrity, calling this method outside the Service layer will trigger a hard SQL error rather than a graceful domain exception.

## ❌ Critical Issues (if any)
*   (None found. Previously reported hydration bugs and ID overwrite risks appear resolved in the current codebase.)

## 🧠 Architectural Completeness Score
*   **95%** (High integrity, solid constraints, minor dead code).

## 📌 Extraction Safety Verdict
*   **SAFE**
    *   **Reasoning**: The module is fully decoupled from user tables (uses `ActorIdentity`), has no hard dependencies on the Auth system, and uses a self-contained schema.
