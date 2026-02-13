# 10. Aggregation & Consistency Model

This chapter documents the **strong-consistency derived layers** and the **synchronous maintenance strategy** for translation statistics.

## 1. Derived Aggregation Layers

The module maintains **two** high-performance derived tables:

1. **`i18n_domain_language_summary`** (domain-first summary)
2. **`i18n_key_stats`** (per-key counters)

Both tables are **derived**, **non-authoritative**, and **fully rebuildable** from authoritative sources.

---

### 1.1 `i18n_domain_language_summary`

**Purpose:** Fast completeness metrics per `(scope, domain, language_id)`.

**Characteristics**

* **Derived:** Computed purely from `i18n_keys` and `i18n_translations` (plus `languages` as identity).
* **Non-Authoritative:** Never treated as source of truth.
* **Rebuildable:** Safe to truncate + rebuild at any time.
* **Synchronous:** Maintained inside the same transaction as writes.

**Stored metrics per `(scope, domain, language_id)`**

* `total_keys`: number of keys in `(scope, domain)`
* `translated_count`: number of translated keys for `(scope, domain, language_id)`
* `missing_count`: `total_keys - translated_count`
  (must always be consistent with the two counters)

---

### 1.2 `i18n_key_stats`

**Purpose:** Fast per-key translated counter to accelerate key-grid queries.

**Characteristics**

* **Derived:** Computed purely from `i18n_keys` and `i18n_translations`.
* **Non-Authoritative:** Optimization only.
* **Rebuildable:** Safe to truncate + rebuild at any time.
* **Synchronous:** Maintained inside the same transaction as writes.

**Stored metrics per `key_id`**

* `translated_count`: number of translations available for that key across all languages
  (no knowledge of “active languages”; it is a raw counter)

---

## 2. Consistency Model (Strong Consistency)

The module strictly enforces **Strong Consistency** for derived layers:

* **Synchronous Updates:** Counters are updated immediately during the write transaction.
* **Single-TX Guarantee:** Derived writes must run in the **same TX** as authoritative writes.
* **No Background Workers:** No queues, jobs, async repairs, or eventual reconciliation.
* **No Cron:** Scheduled tasks are not required for correctness.
* **Immediate Read-After-Write:** A read immediately following a committed write must reflect the new state.

> Derived layers are “fast mirrors”. If drift happens due to manual DB edits, **rebuild** is the canonical recovery.

---

## 3. Write Flow (Authoritative → Derived)

`TranslationWriteService` coordinates with `MissingCounterService` to keep derived layers correct.

### 3.1 Key Creation

When a new key is created:

1. `TranslationWriteService` inserts into `i18n_keys`.
2. `MissingCounterService::onKeyCreated($keyId)` runs inside the same TX.
3. Derived updates:

    * `i18n_domain_language_summary`: `incrementTotalKeys(scope, domain)`
    * `i18n_key_stats`: `createForKey(keyId)` (initialize `translated_count = 0`)

### 3.2 Translation Creation

When a new translation row is inserted (`created = true`):

1. Authoritative insert occurs in `i18n_translations`.
2. `MissingCounterService::onTranslationCreated(keyId, languageId)` runs inside the same TX.
3. Derived updates:

    * `i18n_domain_language_summary`: `incrementTranslated(scope, domain, languageId)`
    * `i18n_key_stats`: `incrementTranslated(keyId)`

### 3.3 Translation Deletion

When a translation row is deleted (`affected > 0`):

1. Authoritative delete occurs in `i18n_translations`.
2. `MissingCounterService::onTranslationDeleted(keyId, languageId)` runs inside the same TX.
3. Derived updates:

    * `i18n_domain_language_summary`: `decrementTranslated(scope, domain, languageId)`
    * `i18n_key_stats`: `decrementTranslated(keyId)` (must never go below 0)

### 3.4 Key Rename / Move (Scope/Domain Change)

When a key is updated such that `(scope, domain)` changes:

1. Authoritative update occurs in `i18n_keys`.
2. `MissingCounterService::onKeyMoved(oldScope, oldDomain, newScope, newDomain)` runs inside the same TX.
3. Derived update strategy:

    * **Safest approach**: rebuild summaries for both affected pairs:

        * `rebuildScopeDomain(oldScope, oldDomain)`
        * `rebuildScopeDomain(newScope, newDomain)`

> This avoids guessing counter deltas across multiple languages.

### 3.5 Key Deletion

When a key is deleted:

1. Authoritative delete occurs in `i18n_keys` (and `i18n_translations` via FK cascade).
2. `MissingCounterService::onKeyDeleted(keyId)` runs in the same TX:

    * fetch key identity (fail-soft if not found)
    * `i18n_domain_language_summary`: `decrementTotalKeys(scope, domain)`
      (implementation may choose rebuildScopeDomain for safety)
    * `i18n_key_stats`: `deleteForKey(keyId)`

---

## 4. Rebuild Strategy (Canonical Recovery)

If drift occurs (manual DB edits, bad import, partial restore), derived layers can be rebuilt deterministically.

### 4.1 Full Rebuild

**Service:** `I18nStatsRebuilder::fullRebuild()`

Guarantees:

* **Single TX** for `truncate + rebuild` to avoid half-state.
* **DB-driven** rebuild (pure SQL `INSERT..SELECT / GROUP BY`).
* **No PHP loops / no N+1**.
* **Idempotent**: safe to run multiple times.

**Required repository ops:**

* `DomainLanguageSummaryRepositoryInterface::truncate()`
* `DomainLanguageSummaryRepositoryInterface::rebuildAll()`
* `KeyStatsRepositoryInterface::truncate()`
* `KeyStatsRepositoryInterface::rebuildAll()`

### 4.2 Partial Rebuild (Repair)

**Repository:** `DomainLanguageSummaryRepositoryInterface::rebuildScopeDomain(scope, domain)`

Use cases:

* key moved across domain/scope
* suspected drift in a specific area
* safe alternative to counter guessing

---

## 5. Repository Contract Notes (Derived Layer Rules)

### 5.1 DomainLanguageSummaryRepositoryInterface

Key rules:

* `translated_count <= total_keys`
* `missing_count = total_keys - translated_count`
* All mutations must run in caller TX.
* `ensureRowExists(scope, domain, languageId)` must be idempotent.

### 5.2 KeyStatsRepositoryInterface

Key rules:

* `translated_count` must be atomic and never go negative.
* Increment/decrement may be implemented as UPSERT to guarantee row existence.
* Rebuild is authoritative-SQL-driven from `i18n_translations`.

---

## 6. Failure Semantics

* **Write path:** fail-hard (typed exceptions for governance/authoritative failures).
* **Derived maintenance:**

    * Normal incremental ops should succeed within the TX.
    * Repair-style operations (rebuildScopeDomain / rebuildAll) are deterministic and may throw if SQL fails.

---

## 7. Non-Goals

To preserve reliability and kernel-grade behavior, the module explicitly rejects:

* **Async reconciliation** (“fix later”)
* **Event buses / external workers**
* **Cron-based correctness**
* **Eventual consistency**
* **Cross-module coupling** (derived logic remains self-contained within `I18n`)

---
