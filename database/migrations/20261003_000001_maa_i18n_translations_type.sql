-- I18n package: nullable exact translation `type` metadata (maatify/php-i18n ADR-020).
--
-- Athar-specific FORWARD migration for an EXISTING database. It is NOT part of the
-- standalone I18n package contract: a fresh install uses
-- vendor/maatify/php-i18n/schema/schema.i18n.sql (the only schema authority) directly,
-- and database/a002.i18n.schema.sql is only its Host deployment projection.
--
-- Precondition: the database is already in the canonical `maa_i18n_*` shape, i.e.
-- 20261001_000001_i18n_tables_to_maa_i18n_namespace.sql has been applied.
--
-- Additive: existing rows receive NULL. Row identity, values, language scope,
-- timestamps, uniqueness, indexes and foreign keys are unchanged. Apply together with
-- the application version that requires maatify/php-i18n ^1.0.0-rc.1: the package's
-- reads and writes select/insert the `type` column.

ALTER TABLE maa_i18n_translations
    ADD COLUMN type VARCHAR(32)
        CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL
        COMMENT 'Exact optional consumer-defined type metadata; no rendering or sanitization (ADR-020)'
        AFTER value,
    ADD CONSTRAINT chk_maa_i18n_translations_type
        CHECK (
            type IS NULL OR (
                CHAR_LENGTH(type) BETWEEN 1 AND 32
                AND type NOT REGEXP CONCAT(
                    '^[[:space:]', CONVERT(CHAR(11) USING utf8mb4), CONVERT(CHAR(12) USING utf8mb4),
                    CONVERT(0xC285 USING utf8mb4), CONVERT(CHAR(92) USING utf8mb4), 'p{Z}]*$'
                )
            )
        ),
    COMMENT = 'Translated values mapped by exact (key + nullable language_code). Optional opaque type metadata. ADR-019, ADR-020.';
