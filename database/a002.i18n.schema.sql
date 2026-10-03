/* ==========================================================================
 * HOST DEPLOYMENT PROJECTION — NOT A SCHEMA AUTHORITY.
 *
 * Byte-identical copy (below this banner) of the I18n Package schema:
 *   vendor/maatify/php-i18n/schema/schema.i18n.sql
 * which is the ONLY authority for the seven Package-owned `maa_i18n_*` tables.
 * Do not edit the tables here: change the Package schema, then re-copy it
 * (tests/user/Unit/I18n/I18nSchemaProjectionTest.php fails on any drift).
 *
 * Existing databases reach this shape through
 * database/migrations/20261001_000001_i18n_tables_to_maa_i18n_namespace.sql.
 * ========================================================================== */

SET FOREIGN_KEY_CHECKS=0;

/* ===========================
 * DROP TABLES (Leaf → Root)
 * =========================== */
DROP TABLE IF EXISTS maa_i18n_domain_language_summary;
DROP TABLE IF EXISTS maa_i18n_key_stats;
DROP TABLE IF EXISTS maa_i18n_translations;
DROP TABLE IF EXISTS maa_i18n_keys;
DROP TABLE IF EXISTS maa_i18n_domain_scopes;
DROP TABLE IF EXISTS maa_i18n_domains;
DROP TABLE IF EXISTS maa_i18n_scopes;

SET FOREIGN_KEY_CHECKS=1;

/* ==========================================================
 * I18N PACKAGE — TRANSLATION LAYER SCHEMA
 * ----------------------------------------------------------
 * Authoritative fresh-install schema of the I18n package.
 * Package-owned tables (the complete set, `maa_i18n_` prefix):
 *   maa_i18n_scopes
 *   maa_i18n_domains
 *   maa_i18n_domain_scopes
 *   maa_i18n_keys
 *   maa_i18n_translations
 *   maa_i18n_domain_language_summary   (derived)
 *   maa_i18n_key_stats                 (derived)
 *
 * Purpose:
 * - Provide structured translation key management
 * - Separate governance (scopes/domains) from identity
 * - Use additive translation rows (no column-per-language)
 * - Support Redis caching and API-first architecture
 *
 * Policies (documented once, here):
 * - Every table has an `id` primary key.
 * - No soft delete anywhere. Keys / translations are hard-deleted;
 *   translations and per-key stats cascade from their key.
 * - Display order: `sort_order` on scopes and domains is maintained
 *   exclusively through maatify/persistence (ScopedOrderingManager);
 *   it is never accepted by create/update operations.
 * - Governance identity (scope code, domain code) is referenced by
 *   code from keys / mappings / summary WITHOUT a foreign key. The
 *   Package serializes "code change vs. new usage" with row locks
 *   inside its Management services (see Management/Service).
 * - Uniqueness is the final race authority: duplicate keys / codes
 *   surface as Package semantic exceptions, never as raw PDO errors.
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

CREATE TABLE maa_i18n_scopes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
        COMMENT 'Internal numeric identity of the scope',

    code VARCHAR(32) NOT NULL
        COMMENT 'Unique machine code of the scope (referenced by keys and mappings)',
    name VARCHAR(64) NOT NULL
        COMMENT 'Human readable scope name',
    description TEXT NULL
        COMMENT 'Optional free-text description of the scope',

    is_active TINYINT(1) NOT NULL DEFAULT 1
        COMMENT '1 = scope accepts governed usage, 0 = disabled',
    sort_order INT NOT NULL DEFAULT 0
        COMMENT 'Display position, managed only through maatify/persistence ordering',

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        COMMENT 'Creation timestamp',

    UNIQUE KEY uq_maa_i18n_scopes_code (code),

    KEY idx_maa_i18n_scopes_is_active (is_active),
    KEY idx_maa_i18n_scopes_sort_order (sort_order)
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

CREATE TABLE maa_i18n_domains (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
        COMMENT 'Internal numeric identity of the domain',

    code VARCHAR(64) NOT NULL
        COMMENT 'Unique machine code of the domain (referenced by keys and mappings)',
    name VARCHAR(128) NOT NULL
        COMMENT 'Human readable domain name',
    description TEXT NULL
        COMMENT 'Optional free-text description of the domain',

    is_active TINYINT(1) NOT NULL DEFAULT 1
        COMMENT '1 = domain accepts governed usage, 0 = disabled',
    sort_order INT NOT NULL DEFAULT 0
        COMMENT 'Display position, managed only through maatify/persistence ordering',

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        COMMENT 'Creation timestamp',

    UNIQUE KEY uq_maa_i18n_domains_code (code),

    KEY idx_maa_i18n_domains_is_active (is_active),
    KEY idx_maa_i18n_domains_sort_order (sort_order)
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
 * Not enforced on maa_i18n_keys.
 * ========================================================== */

CREATE TABLE maa_i18n_domain_scopes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
        COMMENT 'Internal numeric identity of the mapping',

    scope_code VARCHAR(32) NOT NULL
        COMMENT 'Scope code the domain is allowed in',
    domain_code VARCHAR(64) NOT NULL
        COMMENT 'Domain code allowed for the scope',

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        COMMENT 'Creation timestamp',

    UNIQUE KEY uq_maa_i18n_domain_scopes (scope_code, domain_code),

    KEY idx_maa_i18n_domain_scopes_scope (scope_code),
    KEY idx_maa_i18n_domain_scopes_domain (domain_code)
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

CREATE TABLE maa_i18n_keys (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
        COMMENT 'Internal numeric identity of the key',

    scope VARCHAR(32) NOT NULL
        COMMENT 'Scope code the key belongs to',
    domain VARCHAR(64) NOT NULL
        COMMENT 'Domain code the key belongs to',
    key_part VARCHAR(128) NOT NULL
        COMMENT 'Key name inside (scope, domain)',

    description VARCHAR(255) NULL
        COMMENT 'Optional description of the key for translators',

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        COMMENT 'Creation timestamp',

    UNIQUE KEY uq_maa_i18n_keys_identity (scope, domain, key_part),

    KEY idx_maa_i18n_keys_scope_domain (scope, domain),
    KEY idx_maa_i18n_keys_domain_scope (domain, scope),
    KEY idx_maa_i18n_keys_key_part (key_part),
    KEY idx_maa_i18n_keys_scope_domain_key (scope, domain, key_part)
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
 * - Only FK is the internal key_id -> maa_i18n_keys. NO FK to any
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

CREATE TABLE maa_i18n_translations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
        COMMENT 'Internal numeric identity of the translation row',

    key_id BIGINT UNSIGNED NOT NULL
        COMMENT 'Owning translation key (maa_i18n_keys.id)',
    language_code VARCHAR(16)
        CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL
        COMMENT 'Exact Host-owned language code, NULL = exact unlocalized scope (ADR-019)',
    language_code_identity VARCHAR(16)
        CHARACTER SET utf8mb4 COLLATE utf8mb4_bin
        GENERATED ALWAYS AS (COALESCE(language_code, '')) STORED
        COMMENT 'NULL-safe identity of language_code (NULL maps to empty string)',

    value TEXT NOT NULL
        COMMENT 'Translated value; empty string is an authoritative empty translation',
    type VARCHAR(32)
        CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL
        COMMENT 'Exact optional consumer-defined type metadata; no rendering or sanitization (ADR-020)',

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        COMMENT 'Creation timestamp',
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
        COMMENT 'Last update timestamp',

    UNIQUE KEY uq_maa_i18n_translation_unique (key_id, language_code_identity),

    KEY idx_maa_i18n_translations_language_code (language_code),
    KEY idx_maa_i18n_translations_key_id (key_id),

    CONSTRAINT chk_maa_i18n_translations_language_code
        CHECK (language_code IS NULL OR (CHAR_LENGTH(TRIM(language_code)) > 0 AND CHAR_LENGTH(language_code) <= 16)),
    CONSTRAINT chk_maa_i18n_translations_type
        CHECK (
            type IS NULL OR (
                CHAR_LENGTH(type) BETWEEN 1 AND 32
                AND type NOT REGEXP CONCAT(
                    '^[[:space:]', CONVERT(CHAR(11) USING utf8mb4), CONVERT(CHAR(12) USING utf8mb4),
                    CONVERT(0xC285 USING utf8mb4), CONVERT(CHAR(92) USING utf8mb4), 'p{Z}]*$'
                )
            )
        ),

    CONSTRAINT fk_maa_i18n_translation_key
        FOREIGN KEY (key_id)
            REFERENCES maa_i18n_keys(id)
            ON DELETE CASCADE
            ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
    COMMENT='Translated values mapped by exact (key + nullable language_code). Optional opaque type metadata. ADR-019, ADR-020.';

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
 * - Can be fully rebuilt at any time from maa_i18n_keys +
 *   maa_i18n_translations ONLY (no Host language table)
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
 * - No FK to scopes/domains tables (consistent with maa_i18n_keys design)
 * - No FK to any Host language table
 * - NULL-safe identity via language_code_identity
 * ========================================================== */

CREATE TABLE maa_i18n_domain_language_summary (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
        COMMENT 'Internal numeric identity of the summary row',

    scope VARCHAR(32) NOT NULL
        COMMENT 'Scope code of the summarized keys',
    domain VARCHAR(64) NOT NULL
        COMMENT 'Domain code of the summarized keys',

    language_code VARCHAR(16)
        CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL
        COMMENT 'Exact Host-owned language code, NULL = exact unlocalized scope (ADR-019)',
    language_code_identity VARCHAR(16)
        CHARACTER SET utf8mb4 COLLATE utf8mb4_bin
        GENERATED ALWAYS AS (COALESCE(language_code, '')) STORED
        COMMENT 'NULL-safe identity of language_code (NULL maps to empty string)',

    total_keys INT UNSIGNED NOT NULL DEFAULT 0
        COMMENT 'Number of keys in (scope, domain)',
    translated_count INT UNSIGNED NOT NULL DEFAULT 0
        COMMENT 'Number of keys translated in this exact language scope',
    missing_count INT UNSIGNED NOT NULL DEFAULT 0
        COMMENT 'total_keys - translated_count',

    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
        COMMENT 'Last recomputation timestamp',

    UNIQUE KEY uq_maa_i18n_domain_language_summary_identity
        (scope, domain, language_code_identity),

    KEY idx_maa_i18n_domain_language_summary_scope_domain (scope, domain),
    KEY idx_maa_i18n_domain_language_summary_language_code (language_code),

    CONSTRAINT chk_maa_i18n_domain_language_summary_language_code
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
 * - Maintained by the I18n package only
 *
 * Identity:
 * - `id` is the primary key.
 * - `key_id` is the unique Package FK identity: exactly one
 *   stats row per key.
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

CREATE TABLE maa_i18n_key_stats (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
        COMMENT 'Internal numeric identity of the stats row',

    key_id BIGINT UNSIGNED NOT NULL
        COMMENT 'Owning translation key (maa_i18n_keys.id), one stats row per key',

    translated_count INT UNSIGNED NOT NULL DEFAULT 0
        COMMENT 'Number of translation rows the key currently has',

    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
        COMMENT 'Last update timestamp',

    UNIQUE KEY uq_maa_i18n_key_stats_key (key_id),

    CONSTRAINT fk_maa_i18n_key_stats_key
        FOREIGN KEY (key_id)
            REFERENCES maa_i18n_keys(id)
            ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
    COMMENT='Derived per-key translation counters. Non-authoritative.';


/* ==========================================================
 * REBUILD STRATEGY (DOCUMENTATION ONLY)
 * ----------------------------------------------------------
 * Full rebuild can be executed via:
 * - I18nStatsRebuilder::fullRebuild()
 * - Migration script
 * - Maintenance task
 *
 * These derived tables MUST NOT be considered source of truth.
 * ========================================================== */
