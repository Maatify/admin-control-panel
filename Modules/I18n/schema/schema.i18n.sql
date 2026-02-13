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
 * - Requires maatify/language-core
 * - References languages.id via FK
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
                           KEY idx_i18n_keys_key_part (key_part)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
    COMMENT='Canonical structured i18n keys. Library-grade.';


/* ==========================================================
 * 5) TRANSLATIONS (LANGUAGE + KEY → VALUE)
 * ----------------------------------------------------------
 * Stores actual translated values.
 *
 * Rules:
 * - One row per (language_id + key_id)
 * - No NULL values
 * - Clean cascade on key deletion
 * - Depends on language-core library
 * ========================================================== */

CREATE TABLE i18n_translations (
                                   id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

                                   key_id BIGINT UNSIGNED NOT NULL,
                                   language_id INT UNSIGNED NOT NULL,

                                   value TEXT NOT NULL,

                                   created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                   updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,

                                   UNIQUE KEY uq_i18n_translation_unique (key_id, language_id),

                                   KEY idx_i18n_translations_language_id (language_id),
                                   KEY idx_i18n_translations_key_id (key_id),

                                   CONSTRAINT fk_i18n_translation_key
                                       FOREIGN KEY (key_id)
                                           REFERENCES i18n_keys(id)
                                           ON DELETE CASCADE
                                           ON UPDATE CASCADE,

                                   CONSTRAINT fk_i18n_translation_language
                                       FOREIGN KEY (language_id)
                                           REFERENCES languages(id)
                                           ON DELETE CASCADE
                                           ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
    COMMENT='Translated values mapped by (language + key). Additive, cache-friendly, API-ready.';

/* ==========================================================
 * 6) DOMAIN LANGUAGE SUMMARY (DERIVED AGGREGATION LAYER)
 * ----------------------------------------------------------
 * Purpose:
 * - Store per (scope + domain + language) translation completeness
 * - Avoid heavy COUNT/JOIN queries in UI summary pages
 * - Provide fast missing counters for Domain-first workflow
 *
 * Nature:
 * - Derived data (NON-authoritative)
 * - Can be fully rebuilt at any time
 * - Maintained via event-driven updates
 *
 * Update Triggers:
 * - Key create/delete
 * - Translation upsert/delete
 * - Language create/delete
 *
 * Notes:
 * - No FK to scopes/domains tables (consistent with i18n_keys design)
 * - Depends on languages.id via FK
 * - Used only for read optimization (summary endpoints)
 * ========================================================== */

CREATE TABLE i18n_domain_language_summary (
                                              id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

                                              scope VARCHAR(32) NOT NULL,
                                              domain VARCHAR(64) NOT NULL,

                                              language_id INT UNSIGNED NOT NULL,

                                              total_keys INT UNSIGNED NOT NULL DEFAULT 0,
                                              translated_count INT UNSIGNED NOT NULL DEFAULT 0,
                                              missing_count INT UNSIGNED NOT NULL DEFAULT 0,

                                              updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                                                  ON UPDATE CURRENT_TIMESTAMP,

                                              UNIQUE KEY uq_i18n_domain_language_summary_identity
                                                  (scope, domain, language_id),

                                              KEY idx_i18n_domain_language_summary_scope_domain (scope, domain),
                                              KEY idx_i18n_domain_language_summary_language (language_id),

                                              CONSTRAINT fk_i18n_domain_language_summary_language
                                                  FOREIGN KEY (language_id)
                                                      REFERENCES languages(id)
                                                      ON DELETE CASCADE
                                                      ON UPDATE CASCADE

    /* MySQL 8+ only (optional)
    ,
    CONSTRAINT chk_i18n_domain_language_summary_integrity
        CHECK (
            translated_count <= total_keys
            AND missing_count <= total_keys
            AND (translated_count + missing_count) <= total_keys
        )
    */
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
    COMMENT='Derived aggregation table for i18n domain translation completeness. Non-authoritative.';

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
 * - Does NOT depend on language-core events
 * - No knowledge of total languages
 * - Pure per-key counter
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
