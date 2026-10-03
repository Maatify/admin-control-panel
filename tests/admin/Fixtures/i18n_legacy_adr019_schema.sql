/* ==========================================================================
 * HISTORICAL FIXTURE — DO NOT USE AS A SCHEMA SOURCE.
 *
 * The I18n persistence of an EXISTING database in its pre-namespace,
 * ADR-019 shape: the unprefixed `i18n_*` tables exactly as the Package schema
 * defined them at umbrella a18cfb27 (before the `maa_i18n_*` namespace).
 *
 * It exists only as the INPUT of the forward-migration proof
 * (database/migrations/20261001_000001_i18n_tables_to_maa_i18n_namespace.sql,
 * see tests/admin/Integration/I18n/I18nTablesToMaaNamespaceMigrationIntegrationTest.php).
 * The only schema authority is vendor/maatify/php-i18n/schema/schema.i18n.sql.
 * ========================================================================== */

SET FOREIGN_KEY_CHECKS=0;

/* ===========================
 * DROP TABLES (Leaf → Root)
 * =========================== */
DROP TABLE IF EXISTS i18n_domain_language_summary;
DROP TABLE IF EXISTS i18n_key_stats;
DROP TABLE IF EXISTS i18n_translations;
DROP TABLE IF EXISTS i18n_keys;
DROP TABLE IF EXISTS i18n_domain_scopes;
DROP TABLE IF EXISTS i18n_domains;
DROP TABLE IF EXISTS i18n_scopes;

SET FOREIGN_KEY_CHECKS=1;

/* ==========================================================
 * I18N MODULE (TRANSLATION LAYER)
 * ----------------------------------------------------------
 * Purpose:
 * - Provide structured translation key management
 * - Separate governance (scopes/domains) from identity
 * - Use additive translation rows (no column-per-language)
 * - Support Redis caching and API-first architecture
 *
 * Dependencies:
 * - None on any Host language table (ADR-019).
 * - Language identity is an exact, nullable, Host-owned
 *   language_code (NULL = unlocalized scope). No FK/JOIN to
 *   `languages`.
 * ========================================================== */


/* ==========================================================
 * 1) I18N SCOPES (GOVERNANCE)
 * ----------------------------------------------------------
 * Defines logical consumer scopes.
 *
 * Examples:
 * - ct   (Customer)
 * - ad   (Admin)
 * - sys  (System / Emails)
 * - api  (API responses)
 *
 * Used for:
 * - Validation
 * - UI dropdowns
 * - Governance only
 *
 * NOT enforced via FK on keys.
 * ========================================================== */

CREATE TABLE i18n_scopes (
                             id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

                             code VARCHAR(32) NOT NULL,
                             name VARCHAR(64) NOT NULL,
                             description TEXT NULL,

                             is_active TINYINT(1) NOT NULL DEFAULT 1,
                             sort_order INT NOT NULL DEFAULT 0,

                             created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

                             UNIQUE KEY uq_i18n_scopes_code (code),

                             KEY idx_i18n_scopes_is_active (is_active),
                             KEY idx_i18n_scopes_sort_order (sort_order)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
    COMMENT='Governance table for translation scopes.';


/* ==========================================================
 * 2) I18N DOMAINS (GOVERNANCE)
 * ----------------------------------------------------------
 * Defines logical translation domains.
 *
 * Examples:
 * - home
 * - auth
 * - products
 * - emails
 *
 * Used for:
 * - Grouping
 * - UI navigation
 * - Cache boundaries
 *
 * NOT enforced via FK on keys.
 * ========================================================== */

CREATE TABLE i18n_domains (
                              id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

                              code VARCHAR(64) NOT NULL,
                              name VARCHAR(128) NOT NULL,
                              description TEXT NULL,

                              is_active TINYINT(1) NOT NULL DEFAULT 1,
                              sort_order INT NOT NULL DEFAULT 0,

                              created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

                              UNIQUE KEY uq_i18n_domains_code (code),

                              KEY idx_i18n_domains_is_active (is_active),
                              KEY idx_i18n_domains_sort_order (sort_order)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
    COMMENT='Governance table for translation domains.';


/* ==========================================================
 * 3) DOMAIN ↔ SCOPE POLICY MAPPING
 * ----------------------------------------------------------
 * Defines allowed domain usage per scope.
 *
 * Used strictly for:
 * - Validation
 * - UI filtering
 *
 * Not enforced on i18n_keys.
 * ========================================================== */

CREATE TABLE i18n_domain_scopes (
                                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

                                    scope_code VARCHAR(32) NOT NULL,
                                    domain_code VARCHAR(64) NOT NULL,

                                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

                                    UNIQUE KEY uq_i18n_domain_scopes (scope_code, domain_code),

                                    KEY idx_i18n_domain_scopes_scope (scope_code),
                                    KEY idx_i18n_domain_scopes_domain (domain_code)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
    COMMENT='Policy table linking domains to scopes.';


/* ==========================================================
 * 4) I18N KEYS (CANONICAL STRUCTURE)
 * ----------------------------------------------------------
 * Represents structured translation key identity.
 *
 * Identity rule:
 * (scope + domain + key_part) MUST be unique.
 *
 * No implicit parsing.
 * No legacy compatibility.
 * ========================================================== */

CREATE TABLE i18n_keys (
                           id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

                           scope VARCHAR(32) NOT NULL,
                           domain VARCHAR(64) NOT NULL,
                           key_part VARCHAR(128) NOT NULL,

                           description VARCHAR(255) NULL,

                           created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

                           UNIQUE KEY uq_i18n_keys_identity (scope, domain, key_part),

                           KEY idx_i18n_keys_scope_domain (scope, domain),
                           KEY idx_i18n_keys_domain_scope (domain, scope),
                           KEY idx_i18n_keys_key_part (key_part),
                           KEY idx_i18n_keys_scope_domain_key (scope, domain, key_part)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
    COMMENT='Canonical structured i18n keys. Library-grade.';


/* ==========================================================
 * 5) TRANSLATIONS (LANGUAGE CODE + KEY → VALUE)
 * ----------------------------------------------------------
 * Stores actual translated values (ADR-019).
 *
 * Rules:
 * - One row per exact (key_id, language_code)
 * - language_code NULL  = exact unlocalized scope
 * - language_code 'ar'  = exact 'ar' scope
 * - No fallback / default / wildcard semantics
 * - Non-NULL code: not empty, not whitespace-only, <= 16 chars
 * - Code is stored as-is (binary collation: 'ar' <> 'AR');
 *   no normalization
 * - Only FK is the internal key_id -> i18n_keys. NO FK to any
 *   Host language table; the Host owns language semantics.
 * - Clean cascade on key deletion
 *
 * NULL-safe identity:
 * - language_code_identity = COALESCE(language_code, '')
 *   ('' is unambiguous: a non-NULL code is never empty)
 * - UNIQUE (key_id, language_code_identity)
 *
 * Renaming a code is an explicit re-key operation
 * (TranslationWriteService::rekeyLanguageCode), never an
 * in-place identity edit.
 * ========================================================== */

CREATE TABLE i18n_translations (
                                   id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

                                   key_id BIGINT UNSIGNED NOT NULL,
                                   language_code VARCHAR(16)
                                       CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
                                   language_code_identity VARCHAR(16)
                                       CHARACTER SET utf8mb4 COLLATE utf8mb4_bin
                                       GENERATED ALWAYS AS (COALESCE(language_code, '')) STORED,

                                   value TEXT NOT NULL,

                                   created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                   updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,

                                   UNIQUE KEY uq_i18n_translation_unique (key_id, language_code_identity),

                                   KEY idx_i18n_translations_language_code (language_code),
                                   KEY idx_i18n_translations_key_id (key_id),

                                   CONSTRAINT chk_i18n_translations_language_code
                                       CHECK (language_code IS NULL OR (CHAR_LENGTH(TRIM(language_code)) > 0 AND CHAR_LENGTH(language_code) <= 16)),

                                   CONSTRAINT fk_i18n_translation_key
                                       FOREIGN KEY (key_id)
                                           REFERENCES i18n_keys(id)
                                           ON DELETE CASCADE
                                           ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
    COMMENT='Translated values mapped by exact (key + nullable language_code). Host-owned language identity, no fallback. ADR-019.';

/* ==========================================================
 * 6) DOMAIN LANGUAGE SUMMARY (DERIVED AGGREGATION LAYER)
 * ----------------------------------------------------------
 * Purpose:
 * - Store per exact (scope + domain + language_code) translation
 *   completeness
 * - Avoid heavy COUNT/JOIN queries in UI summary pages
 *
 * Nature:
 * - Derived data (NON-authoritative)
 * - Can be fully rebuilt at any time from i18n_keys +
 *   i18n_translations ONLY (no Host language table)
 * - Maintained inside the same transaction as the write
 *
 * Row semantics (ADR-019):
 * - A row exists iff at least one authoritative translation
 *   exists for that exact (scope, domain, language_code)
 * - I18n does NOT know the Host language list and never
 *   pre-creates rows per language
 * - total_keys       = keys in (scope, domain)
 * - translated_count = translations of that exact scope
 * - missing_count    = total_keys - translated_count
 * - A Host language with no row has translated = 0; the Host
 *   composes its own language list with these counts by code
 *
 * Update Triggers:
 * - Key create / move
 * - Translation create / delete
 * - Language-code re-key
 *
 * Notes:
 * - No FK to scopes/domains tables (consistent with i18n_keys design)
 * - No FK to any Host language table
 * - NULL-safe identity via language_code_identity
 * ========================================================== */

CREATE TABLE i18n_domain_language_summary (
                                              id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

                                              scope VARCHAR(32) NOT NULL,
                                              domain VARCHAR(64) NOT NULL,

                                              language_code VARCHAR(16)
                                                  CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
                                              language_code_identity VARCHAR(16)
                                                  CHARACTER SET utf8mb4 COLLATE utf8mb4_bin
                                                  GENERATED ALWAYS AS (COALESCE(language_code, '')) STORED,

                                              total_keys INT UNSIGNED NOT NULL DEFAULT 0,
                                              translated_count INT UNSIGNED NOT NULL DEFAULT 0,
                                              missing_count INT UNSIGNED NOT NULL DEFAULT 0,

                                              updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                                                  ON UPDATE CURRENT_TIMESTAMP,

                                              UNIQUE KEY uq_i18n_domain_language_summary_identity
                                                  (scope, domain, language_code_identity),

                                              KEY idx_i18n_domain_language_summary_scope_domain (scope, domain),
                                              KEY idx_i18n_domain_language_summary_language_code (language_code),

                                              CONSTRAINT chk_i18n_domain_language_summary_language_code
                                                  CHECK (language_code IS NULL OR (CHAR_LENGTH(TRIM(language_code)) > 0 AND CHAR_LENGTH(language_code) <= 16))
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
    COMMENT='Derived exact-scope aggregation for i18n domain translation completeness. Non-authoritative. ADR-019.';

/* ==========================================================
 * 7) I18N KEY STATS (DERIVED AGGREGATION LAYER)
 * ----------------------------------------------------------
 * Purpose:
 * - Store per-key translation counters
 * - Avoid heavy JOIN/COUNT operations in list endpoints
 * - Provide fast per-key completeness metrics
 *
 * Nature:
 * - Derived data (NON-authoritative)
 * - Fully rebuildable at any time
 * - Maintained by i18n module only
 *
 * Update Triggers:
 * - Translation insert/delete
 * - Key create/delete
 *
 * Notes:
 * - Does NOT depend on any Host language data
 * - No knowledge of the Host language universe
 * - Pure per-key counter of actual translation rows
 * ========================================================== */

CREATE TABLE i18n_key_stats (
                                key_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,

                                translated_count INT UNSIGNED NOT NULL DEFAULT 0,

                                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                                    ON UPDATE CURRENT_TIMESTAMP,

                                CONSTRAINT fk_i18n_key_stats_key
                                    FOREIGN KEY (key_id)
                                        REFERENCES i18n_keys(id)
                                        ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
    COMMENT='Derived per-key translation counters. Non-authoritative.';


/* ==========================================================
 * REBUILD STRATEGY (DOCUMENTATION ONLY)
 * ----------------------------------------------------------
 * Full rebuild can be executed via:
 * - CLI command
 * - Migration script
 * - Maintenance task
 *
 * This table MUST NOT be considered source of truth.
 * ========================================================== */
