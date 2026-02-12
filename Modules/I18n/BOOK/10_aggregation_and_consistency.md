# 10. Aggregation & Consistency Model

This chapter documents the strong-consistency aggregation layer and the synchronous maintenance strategy for translation statistics.

## 1. Aggregation Layer

The module maintains a high-performance summary table: `i18n_domain_language_summary`.

### Characteristics
*   **Derived:** Calculated purely from `i18n_keys` and `i18n_translations`.
*   **Non-Authoritative:** This table is **NOT a source of truth**. It is a read-optimization artifact.
*   **Rebuildable:** Can be fully reconstructed from source tables at any time.
*   **Synchronous:** Updated in the same transaction as the write operation.

### Schema
Stores counts per `(scope, domain, language)` tuple:
*   `total_keys`: Count of keys in the domain.
*   `translated_count`: Count of keys with a value for the language.
*   `missing_count`: Derived as `total - translated`.

## 2. Consistency Model

The module strictly enforces **Strong Consistency**.

*   **Synchronous Updates:** All counters are updated immediately during write operations.
*   **No Background Workers:** There are no queues, jobs, or async processes.
*   **No Eventual Consistency:** A read immediately following a write is guaranteed to reflect the new state.
*   **No Cron:** The system does not rely on scheduled tasks for data integrity.

## 3. Write Flow

To maintain strong consistency, the `TranslationWriteService` coordinates with `MissingCounterService`.

### 3.1 Key Creation
When a new key is created:
1.  `TranslationWriteService` inserts into `i18n_keys`.
2.  `MissingCounterService::incrementTotalKeys(domainId)` is called.
3.  All language summaries for that domain increment `total_keys`.

### 3.2 Translation Upsert
When a translation is upserted (`upsertTranslation`):
1.  `TranslationWriteService` performs the upsert.
2.  Returns a `TranslationUpsertResultDTO` indicating if the record was `created` or `updated`.
3.  If `created` is true:
    *   `MissingCounterService::incrementTranslated(domainId, languageId)` is called.
    *   The summary for that specific language increments `translated_keys`.

### 3.3 Translation Deletion
When a translation is deleted (`deleteTranslation`):
1.  `TranslationWriteService` deletes the row.
2.  Returns the number of affected rows (int).
3.  If affected > 0:
    *   `MissingCounterService::decrementTranslated(domainId, languageId)` is called.
    *   The summary decrements `translated_keys`.

## 4. Rebuild Strategy

In the event of data drift or manual intervention (e.g., direct DB edits), the aggregation layer can be rebuilt.

### `MissingCounterRebuilder::fullRebuild()`
1.  **Safe Truncate:** Clears the summary table.
2.  **Deterministic Rebuild:**
    *   Scans all Domains.
    *   Scans all Languages.
    *   Calculates `countByLanguageAndKeyIds` for bulk aggregation.
    *   Inserts fresh rows.
3.  **Idempotent:** Can be run multiple times without side effects.
4.  **Operational Recovery:** Designed to be run manually by admins if needed, but not required for normal operation.

## 5. Updated Repository Contracts

The following repository methods power the consistency model.

### `upsert()`
*   **Returns:** `TranslationUpsertResultDTO`
*   **Fields:** `id` (int), `created` (bool).
*   **Usage:** Used by Service to trigger counter increments.

### `deleteByLanguageAndKey()`
*   **Returns:** `int` (Number of affected rows).
*   **Usage:** Used by Service to trigger counter decrements.

### `countByLanguageAndKeyIds()`
*   **Purpose:** bulk aggregation for rebuilds.

### Key Deletion
*   **Status:** **NOT SUPPORTED**.
*   **Rationale:** Deleting keys breaks referential integrity and historical context. Keys should be deprecated, not deleted.

## 6. Non-Goals

To preserve simplicity and reliability, the module explicitly rejects:

*   **Async Reconciliation:** No "fix it later" logic.
*   **Event Bus:** No reliance on external event systems.
*   **Background Processing:** No dependencies on worker queues.
*   **Key Deletion:** No feature to remove keys (except via manual DB admin if strictly necessary, followed by a rebuild).
*   **Cross-Module Coupling:** The aggregation logic is self-contained within `I18n`.
