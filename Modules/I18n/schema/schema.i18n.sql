SET FOREIGN_KEY_CHECKS=0;

/* ===========================
 * DROP TABLES (Leaf → Root)
 * =========================== */
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

                             UNIQUE KEY uq_i18n_scopes_code (code)
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

                              UNIQUE KEY uq_i18n_domains_code (code)
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

                                    UNIQUE KEY uq_i18n_domain_scopes (scope_code, domain_code)
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

                           UNIQUE KEY uq_i18n_keys_identity (scope, domain, key_part)
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

                                   CONSTRAINT fk_i18n_translation_key
                                       FOREIGN KEY (key_id)
                                           REFERENCES i18n_keys(id)
                                           ON DELETE CASCADE,

                                   CONSTRAINT fk_i18n_translation_language
                                       FOREIGN KEY (language_id)
                                           REFERENCES languages(id)
                                           ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
    COMMENT='Translated values mapped by (language + key). Additive, cache-friendly, API-ready.';
