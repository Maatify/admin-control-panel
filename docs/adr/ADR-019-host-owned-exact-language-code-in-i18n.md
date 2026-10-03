# ADR-019: Host-Owned Language Identity — Exact Nullable `language_code` in I18n

> **Naming note (current namespace).** This record was written when the tables were unprefixed (`i18n_*`).
> The Package-owned tables are now `maa_i18n_scopes`, `maa_i18n_domains`, `maa_i18n_domain_scopes`,
> `maa_i18n_keys`, `maa_i18n_translations`, `maa_i18n_domain_language_summary`, `maa_i18n_key_stats`.
> The decision below is unchanged.

**Status:** ACCEPTED
**Date:** 2026-09-30
**Decision ID:** ADR-019
**Relates to:** ADR-018 (Scope/Domain string codes). ADR-018 is unchanged; it does not decide language identity.

---

## 1. Context

Before this decision `Modules/I18n` was coupled to LanguageCore:

* `i18n_translations.language_id` and `i18n_domain_language_summary.language_id` were FKs to `languages.id`.
* `TranslationReadService` / `TranslationDomainReadService` resolved a language code to an ID through `LanguageRepositoryInterface` and applied a one-level fallback inside I18n.
* `TranslationWriteService` validated that the language exists through LanguageCore.
* The summary repository did `CROSS JOIN languages`, so I18n "knew" every language of the Host.

That makes I18n unable to run without LanguageCore and puts Host policy (which languages exist, which one falls back to which) inside the translation layer.

## 2. Decision

**Language identity is owned by the Host. I18n stores and reads an exact, nullable `language_code` and nothing else about languages.**

1. The I18n runtime/persistence contract uses `?string $languageCode`. `language_id` does not exist in it.
2. `NULL` is the **exact unlocalized scope**. A non-NULL code is the **exact scope of that code**. `NULL` is not a wildcard, not "default" and not "all".
3. **No fallback inside I18n.** A read for `ar` reads `ar` only; a read for `NULL` reads `NULL` only; a miss returns the fail-soft empty result. Fallback is a Host policy (Athar User: `app/Infrastructure/I18n/Translator.php`).
4. **No semantic language validation inside I18n.** I18n does not check that a code exists, is active, is default or is supported. It validates only the technical storage contract (§3).
5. **No LanguageCore dependency** in `Modules/I18n`: no repository, service, DTO or exception from LanguageCore, and no FK/JOIN to `languages` or any other Host language table.
6. **No silent normalization.** Codes are stored exactly as given (no lowercase, no trim-and-store). The column collation is binary (`utf8mb4_bin`) so `ar` and `AR` are distinct identities.

## 3. Storage Contract

* `language_code VARCHAR(16) NULL`.
* Non-NULL values must not be empty or whitespace-only and must be at most 16 characters (CHECK constraint + service-level guard).
* Translation identity: `(key_id, language_code)`, NULL-safe, enforced by a stored generated column:

```text
language_code_identity VARCHAR(16) GENERATED ALWAYS AS (COALESCE(language_code, '')) STORED
UNIQUE (key_id, language_code_identity)
```

  `''` is unambiguous because a non-NULL code can never be empty.
* The only FK is the internal `i18n_translations.key_id → i18n_keys.id`.

## 4. Summary / Coverage Ownership

* `i18n_domain_language_summary` is a derived, non-authoritative, exact-scope read model. Identity: `(scope, domain, language_code)` with the same NULL-safe generated column.
* A summary row exists **iff** at least one authoritative translation exists for that `(scope, domain, language_code)`. I18n never pre-creates rows for Host languages.
* `total_keys` (I18n fact) = keys in `(scope, domain)`; `translated_count` = translations of that exact scope; `missing_count = total_keys − translated_count`.
* A Host language with no row has `translated = 0`; the **Host composes** its language list with the I18n counts by `language_code` (LEFT JOIN, using the Host's own tables) and derives `missing = total_keys − 0`.
* Rebuild is SQL-driven and deterministic from `i18n_keys` + `i18n_translations` only. It does not read `languages`.
* `i18n_key_stats` stays a per-key package-owned counter and does not model a language universe.
* Dashboard reads return exact codes and counts. Names/icons/active flags are Host composition.

## 5. Host Responsibilities

* Owns the language registry (IDs, names, icons, direction, active flag, fallback configuration).
* Resolves any Host identity (e.g. the Admin route `language_id`) to an exact code **before** calling I18n. IDs never cross the I18n boundary.
* Owns locale selection and fallback.
* Owns semantic validation of codes (existence/support) — I18n will store any syntactically valid code, including an unknown one.

## 6. Language-Code Lifecycle / Rename

Because the code is now the stable external identity inside I18n, renaming a language code in the Host changes the identity of its translations.

* I18n exposes an explicit, package-owned operation `TranslationWriteService::rekeyLanguageCode(string $oldCode, string $newCode)`: it re-keys the authoritative `i18n_translations` rows and rebuilds the affected derived summary rows inside one I18n transaction. It fails hard (`I18nConflictException`) if `newCode` already owns translations, and does not validate the language semantically.
* The **Host** orchestrates the rename. In Athar, `LanguageCodeChangeService` (AdminKernel) runs, in one database transaction on the shared connection: resolve the language → validate the new code against the storage contract → `LanguageManagementService::updateLanguageCode` → `rekeyLanguageCode(old, new)`. Any failure rolls both sides back. Renaming a code through LanguageCore alone (leaving I18n rows under the old code) is not supported and no path in Athar performs it.
* No FK cascade is used and I18n never edits LanguageCore data.

## 7. Trade-offs

* No database-level referential integrity between translations and Host languages. A code with no Host language is an orphan **by Host policy**, not an I18n error; the Host decides whether to surface or purge it.
* Fallback that used to be transparent inside I18n is now explicit Host code. Athar User applies the application default in `Translator` and the configured per-language fallback (`fallback_language_id`) in `LanguageFallbackTranslationSource`; both are explicit Host code now.
* Binary collation requires Host joins against `languages.code` to state `COLLATE utf8mb4_bin` explicitly.

## 8. Rejected Alternatives

* **Keep `language_id`, hide LanguageCore behind a port** — keeps the Host-generated integer as the package's identity; rejected for the same extraction-readiness reasons as ADR-018.
* **Default-language semantics for `NULL`** — would make I18n own a default/fallback policy; rejected.
* **Blocking language-code changes in the Host** — safe but removes an existing Admin capability; rejected in favour of atomic re-keying.

## 9. Conditions for Re-evaluation

Revisit if a consumer needs referential integrity to a language registry inside the same schema, or if language identity must become non-textual.

---

**Final Statement:** I18n stores exact, opaque, Host-owned language codes. Everything semantic about languages is the Host's job.
