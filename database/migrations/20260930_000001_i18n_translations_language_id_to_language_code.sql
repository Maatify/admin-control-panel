-- ADR-019: I18n language identity moves from the Host-generated
-- `languages.id` to the exact, nullable, Host-owned `language_code`.
--
-- Athar-specific data migration for an EXISTING database. It is NOT part of the
-- future standalone I18n package contract (fresh installs use
-- vendor/maatify/php-i18n/schema/schema.i18n.sql directly).
--
-- Behaviour
-- ---------
--  * i18n_translations: every row keeps its id, key_id, value and timestamps.
--    Its language identity is translated `languages.id -> languages.code`.
--    No row is dropped, merged or duplicated.
--  * i18n_domain_language_summary: derived, non-authoritative. It is dropped,
--    re-created in the new shape and REBUILT from i18n_keys +
--    i18n_translations (same statement as MysqlDomainLanguageSummaryRepository::rebuildAll()).
--  * i18n_key_stats is per-key and language-agnostic: unchanged.
--
-- Safety order (validate BEFORE the destructive switch)
-- -----------------------------------------------------
--  Steps 1-4 are additive: they add and fill the new columns, then add the
--  NULL-safe UNIQUE and the CHECKs. If any legacy row did not resolve to a
--  Host code (orphan language_id), if any translation would collapse into a
--  duplicate (key_id, language_code), or a code violates the storage contract
--  (empty / whitespace-only / longer than 16 characters), step 3 or 4 FAILS and
--  the old `language_id` schema is still intact. Steps 5-6 (drop the FK / old
--  unique / old column) run only after that. Run with a client that stops on
--  the first error (the `mysql` CLI does by default) and take a backup first.
--
-- Prerequisite: the migrated app version (which reads/writes language_code)
-- must be deployed together with this migration; the old app version cannot use
-- the new schema.

-- 1) Additive: new nullable exact-code column (binary collation: no silent
--    case folding, 'ar' <> 'AR').
ALTER TABLE `i18n_translations`
    ADD COLUMN `language_code` VARCHAR(16)
        CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL AFTER `language_id`;

-- 2) Copy identity: languages.id -> languages.code (exact, no normalization).
--    `updated_at` is assigned to itself so its ON UPDATE CURRENT_TIMESTAMP does
--    not rewrite the timestamp of every translation.
UPDATE `i18n_translations` t
    JOIN `languages` l ON l.`id` = t.`language_id`
SET t.`language_code` = l.`code`,
    t.`updated_at` = t.`updated_at`;

-- 3a) Every legacy row MUST have resolved to a Host code. A row whose
--     language_id matched no `languages` row (orphan from an import with FK
--     checks off, schema drift...) would stay NULL and, once language_id is
--     dropped, be indistinguishable from an intentional unlocalized (NULL)
--     translation. This temporary CHECK fails the migration right here, while
--     the old language_id identity is still intact. Dropped again in step 5.
ALTER TABLE `i18n_translations`
    ADD CONSTRAINT `chk_i18n_translations_legacy_mapped`
        CHECK (`language_code` IS NOT NULL);

-- 3b) Storage contract on the authoritative column BEFORE anything is dropped.
--     (Fails here on an empty / whitespace-only code.)
ALTER TABLE `i18n_translations`
    ADD CONSTRAINT `chk_i18n_translations_language_code`
        CHECK (`language_code` IS NULL OR (CHAR_LENGTH(TRIM(`language_code`)) > 0 AND CHAR_LENGTH(`language_code`) <= 16));

-- 4) NULL-safe logical identity (key_id, language_code). Fails here if two
--    rows would collapse into the same identity. Temporary index name because
--    the old unique index still owns the final name.
ALTER TABLE `i18n_translations`
    ADD COLUMN `language_code_identity` VARCHAR(16)
        CHARACTER SET utf8mb4 COLLATE utf8mb4_bin
        GENERATED ALWAYS AS (COALESCE(`language_code`, '')) STORED AFTER `language_code`,
    ADD UNIQUE KEY `uq_i18n_translation_unique_new` (`key_id`, `language_code_identity`);

-- 5) Destructive switch (only reachable when 1-4 succeeded).
ALTER TABLE `i18n_translations`
    DROP FOREIGN KEY `fk_i18n_translation_language`;

ALTER TABLE `i18n_translations`
    DROP INDEX `uq_i18n_translation_unique`,
    DROP INDEX `idx_i18n_translations_language_id`,
    DROP COLUMN `language_id`;

ALTER TABLE `i18n_translations`
    DROP CONSTRAINT `chk_i18n_translations_legacy_mapped`;

ALTER TABLE `i18n_translations`
    RENAME INDEX `uq_i18n_translation_unique_new` TO `uq_i18n_translation_unique`,
    ADD KEY `idx_i18n_translations_language_code` (`language_code`),
    COMMENT = 'Translated values mapped by exact (key + nullable language_code). Host-owned language identity, no fallback. ADR-019.';

-- 6) Derived summary: re-create in the exact-scope shape and rebuild from the
--    authoritative tables only (no `languages` read).
DROP TABLE IF EXISTS `i18n_domain_language_summary`;

CREATE TABLE `i18n_domain_language_summary` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    `scope` VARCHAR(32) NOT NULL,
    `domain` VARCHAR(64) NOT NULL,

    `language_code` VARCHAR(16)
        CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
    `language_code_identity` VARCHAR(16)
        CHARACTER SET utf8mb4 COLLATE utf8mb4_bin
        GENERATED ALWAYS AS (COALESCE(`language_code`, '')) STORED,

    `total_keys` INT UNSIGNED NOT NULL DEFAULT 0,
    `translated_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `missing_count` INT UNSIGNED NOT NULL DEFAULT 0,

    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY `uq_i18n_domain_language_summary_identity`
        (`scope`, `domain`, `language_code_identity`),

    KEY `idx_i18n_domain_language_summary_scope_domain` (`scope`, `domain`),
    KEY `idx_i18n_domain_language_summary_language_code` (`language_code`),

    CONSTRAINT `chk_i18n_domain_language_summary_language_code`
        CHECK (`language_code` IS NULL OR (CHAR_LENGTH(TRIM(`language_code`)) > 0 AND CHAR_LENGTH(`language_code`) <= 16))
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Derived exact-scope aggregation for i18n domain translation completeness. Non-authoritative. ADR-019.';

INSERT INTO `i18n_domain_language_summary`
    (`scope`, `domain`, `language_code`, `total_keys`, `translated_count`, `missing_count`)
SELECT
    x.`scope`,
    x.`domain`,
    x.`language_code`,
    kt.`total_keys`,
    x.`translated_count`,
    kt.`total_keys` - x.`translated_count`
FROM (
    SELECT
        k.`scope`,
        k.`domain`,
        t.`language_code`,
        COUNT(*) AS `translated_count`
    FROM `i18n_keys` k
    JOIN `i18n_translations` t
        ON t.`key_id` = k.`id`
    GROUP BY k.`scope`, k.`domain`, t.`language_code`
) x
JOIN (
    SELECT `scope`, `domain`, COUNT(*) AS `total_keys`
    FROM `i18n_keys`
    GROUP BY `scope`, `domain`
) kt
    ON kt.`scope` = x.`scope`
   AND kt.`domain` = x.`domain`;
