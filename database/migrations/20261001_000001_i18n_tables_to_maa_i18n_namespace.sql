-- I18n package persistence namespace: legacy `i18n_*` tables -> canonical `maa_i18n_*`.
--
-- Athar-specific FORWARD migration for an EXISTING database. It is NOT part of the
-- standalone I18n package contract: a fresh install uses
-- vendor/maatify/php-i18n/schema/schema.i18n.sql (the only schema authority) directly, and
-- database/a002.i18n.schema.sql is only its Host deployment projection.
--
-- Precondition: the database is already in the ADR-019 shape, i.e.
-- 20260930_000001_i18n_translations_language_id_to_language_code.sql has been
-- applied (i18n_translations / i18n_domain_language_summary own language_code).
-- Apply the migrated application version together with this migration: the old
-- application version reads the legacy names and cannot use the new ones.
--
-- What it preserves (nothing is copied, dropped or rewritten):
--   * RENAME TABLE keeps every row, id, timestamp and value: scopes, domains,
--     mappings, keys, translations (exact language_code, NULL scope, binary
--     case, empty values included) and both derived tables.
--   * The foreign keys keep pointing at the renamed parent tables.
--   * maa_i18n_key_stats gets its OWN `id` primary key; `key_id` becomes the
--     unique Package FK identity (exactly one stats row per key, counters kept).
--
-- What it brings up to the current Package standard on the renamed tables:
--   * index / constraint names with the `maa_i18n_` prefix (identical to a fresh
--     install of the Package schema);
--   * meaningful COMMENT on every column.
--
-- Safety order (validate BEFORE the first change)
-- -----------------------------------------------
--  Step 0 fails (unknown column) when the ADR-019 migration was not applied,
--  before anything is renamed. Step 1 is ONE atomic multi-table RENAME: MySQL
--  renames all seven tables or none, and it fails when any `maa_i18n_*` table
--  already exists or any legacy table is missing. Steps 2-8 only restate
--  names / comments (and add the key_stats id) on the renamed tables.
--  Run with a client that stops on the first error (the `mysql` CLI does by
--  default) and take a backup first.
--
-- Derived data: nothing in the derived tables changes meaning, so no rebuild is
-- required; `php scripts/i18n_rebuild_summary.php` remains the
-- deterministic way to prove equivalence at any time.

-- 0) Guard: fail early, before any rename, when the ADR-019 shape is missing.
ALTER TABLE `i18n_translations`
    ALTER COLUMN `language_code` SET DEFAULT NULL;

ALTER TABLE `i18n_domain_language_summary`
    ALTER COLUMN `language_code` SET DEFAULT NULL;

-- 1) Atomic rename of the complete Package-owned table set.
RENAME TABLE
    `i18n_scopes`                  TO `maa_i18n_scopes`,
    `i18n_domains`                 TO `maa_i18n_domains`,
    `i18n_domain_scopes`           TO `maa_i18n_domain_scopes`,
    `i18n_keys`                    TO `maa_i18n_keys`,
    `i18n_translations`            TO `maa_i18n_translations`,
    `i18n_domain_language_summary` TO `maa_i18n_domain_language_summary`,
    `i18n_key_stats`               TO `maa_i18n_key_stats`;

-- 2) maa_i18n_scopes
ALTER TABLE `maa_i18n_scopes`
    RENAME INDEX `uq_i18n_scopes_code` TO `uq_maa_i18n_scopes_code`,
    RENAME INDEX `idx_i18n_scopes_is_active` TO `idx_maa_i18n_scopes_is_active`,
    RENAME INDEX `idx_i18n_scopes_sort_order` TO `idx_maa_i18n_scopes_sort_order`,
    MODIFY COLUMN `id` INT UNSIGNED NOT NULL AUTO_INCREMENT
        COMMENT 'Internal numeric identity of the scope',
    MODIFY COLUMN `code` VARCHAR(32) NOT NULL
        COMMENT 'Unique machine code of the scope (referenced by keys and mappings)',
    MODIFY COLUMN `name` VARCHAR(64) NOT NULL
        COMMENT 'Human readable scope name',
    MODIFY COLUMN `description` TEXT NULL
        COMMENT 'Optional free-text description of the scope',
    MODIFY COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1
        COMMENT '1 = scope accepts governed usage, 0 = disabled',
    MODIFY COLUMN `sort_order` INT NOT NULL DEFAULT 0
        COMMENT 'Display position, managed only through maatify/persistence ordering',
    MODIFY COLUMN `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        COMMENT 'Creation timestamp';

-- 3) maa_i18n_domains
ALTER TABLE `maa_i18n_domains`
    RENAME INDEX `uq_i18n_domains_code` TO `uq_maa_i18n_domains_code`,
    RENAME INDEX `idx_i18n_domains_is_active` TO `idx_maa_i18n_domains_is_active`,
    RENAME INDEX `idx_i18n_domains_sort_order` TO `idx_maa_i18n_domains_sort_order`,
    MODIFY COLUMN `id` INT UNSIGNED NOT NULL AUTO_INCREMENT
        COMMENT 'Internal numeric identity of the domain',
    MODIFY COLUMN `code` VARCHAR(64) NOT NULL
        COMMENT 'Unique machine code of the domain (referenced by keys and mappings)',
    MODIFY COLUMN `name` VARCHAR(128) NOT NULL
        COMMENT 'Human readable domain name',
    MODIFY COLUMN `description` TEXT NULL
        COMMENT 'Optional free-text description of the domain',
    MODIFY COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1
        COMMENT '1 = domain accepts governed usage, 0 = disabled',
    MODIFY COLUMN `sort_order` INT NOT NULL DEFAULT 0
        COMMENT 'Display position, managed only through maatify/persistence ordering',
    MODIFY COLUMN `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        COMMENT 'Creation timestamp';

-- 4) maa_i18n_domain_scopes
ALTER TABLE `maa_i18n_domain_scopes`
    RENAME INDEX `uq_i18n_domain_scopes` TO `uq_maa_i18n_domain_scopes`,
    RENAME INDEX `idx_i18n_domain_scopes_scope` TO `idx_maa_i18n_domain_scopes_scope`,
    RENAME INDEX `idx_i18n_domain_scopes_domain` TO `idx_maa_i18n_domain_scopes_domain`,
    MODIFY COLUMN `id` INT UNSIGNED NOT NULL AUTO_INCREMENT
        COMMENT 'Internal numeric identity of the mapping',
    MODIFY COLUMN `scope_code` VARCHAR(32) NOT NULL
        COMMENT 'Scope code the domain is allowed in',
    MODIFY COLUMN `domain_code` VARCHAR(64) NOT NULL
        COMMENT 'Domain code allowed for the scope',
    MODIFY COLUMN `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        COMMENT 'Creation timestamp';

-- 5) maa_i18n_keys
ALTER TABLE `maa_i18n_keys`
    RENAME INDEX `uq_i18n_keys_identity` TO `uq_maa_i18n_keys_identity`,
    RENAME INDEX `idx_i18n_keys_scope_domain` TO `idx_maa_i18n_keys_scope_domain`,
    RENAME INDEX `idx_i18n_keys_domain_scope` TO `idx_maa_i18n_keys_domain_scope`,
    RENAME INDEX `idx_i18n_keys_key_part` TO `idx_maa_i18n_keys_key_part`,
    RENAME INDEX `idx_i18n_keys_scope_domain_key` TO `idx_maa_i18n_keys_scope_domain_key`,
    MODIFY COLUMN `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT
        COMMENT 'Internal numeric identity of the key',
    MODIFY COLUMN `scope` VARCHAR(32) NOT NULL
        COMMENT 'Scope code the key belongs to',
    MODIFY COLUMN `domain` VARCHAR(64) NOT NULL
        COMMENT 'Domain code the key belongs to',
    MODIFY COLUMN `key_part` VARCHAR(128) NOT NULL
        COMMENT 'Key name inside (scope, domain)',
    MODIFY COLUMN `description` VARCHAR(255) NULL
        COMMENT 'Optional description of the key for translators',
    MODIFY COLUMN `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        COMMENT 'Creation timestamp';

-- 6) maa_i18n_translations (exact nullable binary language_code: ADR-019, unchanged)
ALTER TABLE `maa_i18n_translations`
    DROP FOREIGN KEY `fk_i18n_translation_key`,
    DROP CONSTRAINT `chk_i18n_translations_language_code`,
    RENAME INDEX `uq_i18n_translation_unique` TO `uq_maa_i18n_translation_unique`,
    RENAME INDEX `idx_i18n_translations_language_code` TO `idx_maa_i18n_translations_language_code`,
    RENAME INDEX `idx_i18n_translations_key_id` TO `idx_maa_i18n_translations_key_id`,
    MODIFY COLUMN `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT
        COMMENT 'Internal numeric identity of the translation row',
    MODIFY COLUMN `key_id` BIGINT UNSIGNED NOT NULL
        COMMENT 'Owning translation key (maa_i18n_keys.id)',
    MODIFY COLUMN `language_code` VARCHAR(16)
        CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL
        COMMENT 'Exact Host-owned language code, NULL = exact unlocalized scope (ADR-019)',
    MODIFY COLUMN `language_code_identity` VARCHAR(16)
        CHARACTER SET utf8mb4 COLLATE utf8mb4_bin
        GENERATED ALWAYS AS (COALESCE(`language_code`, '')) STORED
        COMMENT 'NULL-safe identity of language_code (NULL maps to empty string)',
    MODIFY COLUMN `value` TEXT NOT NULL
        COMMENT 'Translated value; empty string is an authoritative empty translation',
    MODIFY COLUMN `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        COMMENT 'Creation timestamp',
    MODIFY COLUMN `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
        COMMENT 'Last update timestamp';

ALTER TABLE `maa_i18n_translations`
    ADD CONSTRAINT `chk_maa_i18n_translations_language_code`
        CHECK (`language_code` IS NULL OR (CHAR_LENGTH(TRIM(`language_code`)) > 0 AND CHAR_LENGTH(`language_code`) <= 16)),
    ADD CONSTRAINT `fk_maa_i18n_translation_key`
        FOREIGN KEY (`key_id`) REFERENCES `maa_i18n_keys` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE;

-- 7) maa_i18n_domain_language_summary (derived, non-authoritative)
ALTER TABLE `maa_i18n_domain_language_summary`
    DROP CONSTRAINT `chk_i18n_domain_language_summary_language_code`,
    RENAME INDEX `uq_i18n_domain_language_summary_identity` TO `uq_maa_i18n_domain_language_summary_identity`,
    RENAME INDEX `idx_i18n_domain_language_summary_scope_domain` TO `idx_maa_i18n_domain_language_summary_scope_domain`,
    RENAME INDEX `idx_i18n_domain_language_summary_language_code` TO `idx_maa_i18n_domain_language_summary_language_code`,
    MODIFY COLUMN `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT
        COMMENT 'Internal numeric identity of the summary row',
    MODIFY COLUMN `scope` VARCHAR(32) NOT NULL
        COMMENT 'Scope code of the summarized keys',
    MODIFY COLUMN `domain` VARCHAR(64) NOT NULL
        COMMENT 'Domain code of the summarized keys',
    MODIFY COLUMN `language_code` VARCHAR(16)
        CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL
        COMMENT 'Exact Host-owned language code, NULL = exact unlocalized scope (ADR-019)',
    MODIFY COLUMN `language_code_identity` VARCHAR(16)
        CHARACTER SET utf8mb4 COLLATE utf8mb4_bin
        GENERATED ALWAYS AS (COALESCE(`language_code`, '')) STORED
        COMMENT 'NULL-safe identity of language_code (NULL maps to empty string)',
    MODIFY COLUMN `total_keys` INT UNSIGNED NOT NULL DEFAULT 0
        COMMENT 'Number of keys in (scope, domain)',
    MODIFY COLUMN `translated_count` INT UNSIGNED NOT NULL DEFAULT 0
        COMMENT 'Number of keys translated in this exact language scope',
    MODIFY COLUMN `missing_count` INT UNSIGNED NOT NULL DEFAULT 0
        COMMENT 'total_keys - translated_count',
    MODIFY COLUMN `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        COMMENT 'Last recomputation timestamp';

ALTER TABLE `maa_i18n_domain_language_summary`
    ADD CONSTRAINT `chk_maa_i18n_domain_language_summary_language_code`
        CHECK (`language_code` IS NULL OR (CHAR_LENGTH(TRIM(`language_code`)) > 0 AND CHAR_LENGTH(`language_code`) <= 16));

-- 8) maa_i18n_key_stats (derived): own `id` primary key, `key_id` = unique Package FK identity
ALTER TABLE `maa_i18n_key_stats`
    DROP FOREIGN KEY `fk_i18n_key_stats_key`,
    DROP PRIMARY KEY,
    ADD COLUMN `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT
        COMMENT 'Internal numeric identity of the stats row' PRIMARY KEY FIRST,
    ADD UNIQUE KEY `uq_maa_i18n_key_stats_key` (`key_id`),
    MODIFY COLUMN `key_id` BIGINT UNSIGNED NOT NULL
        COMMENT 'Owning translation key (maa_i18n_keys.id), one stats row per key',
    MODIFY COLUMN `translated_count` INT UNSIGNED NOT NULL DEFAULT 0
        COMMENT 'Number of translation rows the key currently has',
    MODIFY COLUMN `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        COMMENT 'Last update timestamp';

ALTER TABLE `maa_i18n_key_stats`
    ADD CONSTRAINT `fk_maa_i18n_key_stats_key`
        FOREIGN KEY (`key_id`) REFERENCES `maa_i18n_keys` (`id`)
        ON DELETE CASCADE;
